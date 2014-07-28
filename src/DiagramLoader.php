<?php

/*
 * This file is part of KoolKode BPMN.
*
* (c) Martin Schröder <m.schroeder2007@gmail.com>
*
* For the full copyright and license information, please view the LICENSE
* file that was distributed with this source code.
*/

namespace KoolKode\BPMN;

use KoolKode\Xml\XmlDocumentBuilder;

/**
 * Reads process models from BPMN 2.0 process and collaboration diagram files.
 * 
 * @author Martin Schröder
 */
class DiagramLoader
{
	const NS_MODEL = 'http://www.omg.org/spec/BPMN/20100524/MODEL';
	const NS_DI = 'http://www.omg.org/spec/BPMN/20100524/DI';
	const NS_DC = 'http://www.omg.org/spec/DD/20100524/DC';
	const NS_XSI = 'http://www.w3.org/2001/XMLSchema-instance';
	const NS_IMPL = 'http://activiti.org/bpmn';
	
	public function parseDiagramFile($file)
	{
		return $this->parseDiagram((new XmlDocumentBuilder())->buildDocument(new \SplFileInfo($file)));
	}
	
	public function parseDiagram(\DOMDocument $xml)
	{
		$result = [];
		$xpath = $this->createXPath($xml);
		
		$messages = [];
		
		foreach($xpath->query('/m:definitions/m:message[@id][@name]') as $messageElement)
		{
			$messages[trim($messageElement->getAttribute('id'))] = trim($messageElement->getAttribute('name'));
		}
		
		$signals = [];
			
		foreach($xpath->query('/m:definitions/m:signal[@id][@name]') as $signalElement)
		{
			$signals[trim($signalElement->getAttribute('id'))] = trim($signalElement->getAttribute('name'));
		}
		
		foreach($xpath->query('/m:definitions/m:process[@id][@isExecutable = "true"]') as $processElement)
		{
			$title = $processElement->hasAttribute('name') ? trim($processElement->getAttribute('name')) : '';
			$result[] = $builder = new BusinessProcessBuilder($processElement->getAttribute('id'), $title);
			
			foreach($xpath->query('m:*[@id]', $processElement) as $el)
			{
				$id = $el->getAttribute('id');
				
				switch($el->localName)
				{
					case 'startEvent':
						
						// TODO: Support different event types...
						
						foreach($xpath->query('m:messageEventDefinition', $el) as $messageElement)
						{
							$message = $messages[$messageElement->getAttribute('messageRef')];
							$builder->messageStartEvent($id, $message);
							
							break 2;
						}
						
						$builder->startEvent($id);
						
						break;
					case 'endEvent':
						$builder->endEvent($id);
						
						// TODO: Support different event types...
						
						break;
					case 'serviceTask':
						$name = $el->hasAttribute('name') ? trim($el->getAttribute('name')) : '';
						
						if($el->hasAttributeNS(self::NS_IMPL, 'class'))
						{
							$builder->delegateTask($id, $el->getAttributeNS(self::NS_IMPL, 'class'), $name);
						}
						elseif($el->hasAttributeNS(self::NS_IMPL, 'expression'))
						{
							$builder->expressionTask($id, $el->getAttributeNS(self::NS_IMPL, 'expression'), $name);
						}
						else
						{
							$builder->serviceTask($id, $name);
						}
						break;
					case 'userTask':
						$name = $el->hasAttribute('name') ? trim($el->getAttribute('name')) : '';
						$builder->userTaks($id, $name);
						break;
					case 'scriptTask':
						$name = $el->hasAttribute('name') ? trim($el->getAttribute('name')) : '';
						$language = strtolower(trim($el->getAttribute('scriptFormat')));
						$script = '';
						
						foreach($xpath->query('m:script', $el) as $scriptElement)
						{
							$script .= $scriptElement->textContent;
						}
						
						$builder->scriptTask($id, $language, $script, $name);
						break;
					case 'sendTask':
						$name = $el->hasAttribute('name') ? trim($el->getAttribute('name')) : '';
						$builder->intermediateMessageThrowEvent($id, $name);
						break;
					case 'sequenceFlow':
						
						$condition = NULL;
						foreach($xpath->query('m:conditionExpression', $el) as $conditionElement)
						{
							$type = (string)$conditionElement->getAttributeNS(self::NS_XSI, 'type');
							$type = explode(':', $type, 2);
							
							if(count($type == 2))
							{
								$uri = $conditionElement->lookupNamespaceURI($type[0]);
								
								if($uri == self::NS_MODEL && $type[1] == 'tFormalExpression')
								{
									$condition = trim($conditionElement->textContent);
								}
							}
						}
						
						$builder->sequenceFlow($id, $el->getAttribute('sourceRef'), $el->getAttribute('targetRef'), $condition);
						break;
					case 'exclusiveGateway':
						$defaultFlow = $el->hasAttribute('default') ? $el->getAttribute('default') : NULL;
						$builder->exclusiveGateway($id, $defaultFlow);
						break;
					case 'parallelGateway':
						$builder->parallelGateway($id);
						break;
					case 'intermediateCatchEvent':
						
						foreach($xpath->query('m:messageEventDefinition', $el) as $messageElement)
						{
							$message = $messages[$messageElement->getAttribute('messageRef')];
							$builder->intermediateMessageCatchEvent($id, $message);
							
							break 2;
						}
						
						foreach($xpath->query('m:signalEventDefinition', $el) as $signalElement)
						{
							$signal = $signals[$signalElement->getAttribute('signalRef')];
							$builder->intermediateSignalCatchEvent($id, $signal);
							
							break 2;
						}
						
						// TODO: Better fallback behavior for unimplemented nodes?
						$builder->serviceTask($id, 'N/A');
						
						break;
					case 'intermediateThrowEvent':
						
						$name = $el->hasAttribute('name') ? trim($el->getAttribute('name')) : '';
						
						foreach($xpath->query('m:messageEventDefinition', $el) as $def)
						{
							$builder->intermediateMessageThrowEvent($id, $name);
							
							break 2;
						}
						
						foreach($xpath->query('m:signalEventDefinition', $el) as $def)
						{
							$signal = $signals[$def->getAttribute('signalRef')];
							$builder->intermediateSignalThrowEvent($id, $signal);
								
							break 2;
						}
						
						// TODO: Better fallback behavior for unimplemented nodes?
						$builder->serviceTask($id, 'N/A');
						
						break;
					default:
						
						// TODO: Better fallback behavior for unimplemented nodes?
						$builder->serviceTask($id, 'N/A');
				}
			}
		}
		
		if(empty($result))
		{
			throw new \OutOfBoundsException(sprintf('No process definition(s) found'));
		}
		
		return $result;
	}
	
	protected function createXPath(\DOMDocument $xml)
	{
		$xpath = new \DOMXPath($xml);
		$xpath->registerNamespace('m', self::NS_MODEL);
		$xpath->registerNamespace('di', self::NS_DI);
		$xpath->registerNamespace('dc', self::NS_DC);
		$xpath->registerNamespace('xsi', self::NS_XSI);
		$xpath->registerNamespace('i', self::NS_IMPL);
		
		return $xpath;
	}
}
