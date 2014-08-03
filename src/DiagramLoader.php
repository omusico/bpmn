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
			
			foreach($xpath->query('.//m:*[@id]', $processElement) as $el)
			{
				$id = $el->getAttribute('id');
				
				switch($el->localName)
				{
					case 'startEvent':
						
						foreach($xpath->query('m:messageEventDefinition', $el) as $messageElement)
						{
							$message = $messages[$messageElement->getAttribute('messageRef')];
							$builder->messageStartEvent($id, $message, $el->getAttribute('name'));
							
							break 2;
						}
						
						foreach($xpath->query('m:signalEventDefinition', $el) as $signalElement)
						{
							$signal = $signals[$signalElement->getAttribute('signalRef')];
							$builder->signalStartEvent($id, $signal, $el->getAttribute('name'));
								
							break 2;
						}
						
						$builder->startEvent($id, $el->getAttribute('name'));
						
						break;
					case 'endEvent':
						
						foreach($xpath->query('m:messageEventDefinition', $el) as $def)
						{
							$builder->messageEndEvent($id, $el->getAttribute('name'));
								
							break 2;
						}
						
						foreach($xpath->query('m:signalEventDefinition', $el) as $def)
						{
							$signal = $signals[$def->getAttribute('signalRef')];
							$builder->signalEndEvent($id, $signal, $el->getAttribute('name'));
						
							break 2;
						}
						
						$builder->endEvent($id, $el->getAttribute('name'));
						
						break;
					case 'serviceTask':
						
						if($el->hasAttributeNS(self::NS_IMPL, 'class') && '' !== trim($el->getAttributeNS(self::NS_IMPL, 'class')))
						{
							$delegateTask = $builder->delegateTask($id, $el->getAttributeNS(self::NS_IMPL, 'class'), $el->getAttribute('name'));
							$delegateTask->setDocumentation($builder->stringExp($this->getDocumentation($el)));
							
							break;
						}
						
						if($el->hasAttributeNS(self::NS_IMPL, 'expression') && '' !== $el->getAttributeNS(self::NS_IMPL, 'expression'))
						{
							$expressionTask = $builder->expressionTask($id, $el->getAttributeNS(self::NS_IMPL, 'expression'), $el->getAttribute('name'));
							$expressionTask->setDocumentation($builder->stringExp($this->getDocumentation($el)));
							
							if($el->hasAttributeNS(self::NS_IMPL, 'resultVariable'))
							{
								$expressionTask->setResultVariable($el->getAttributeNS(self::NS_IMPL, 'resultVariable'));
							}
							
							break;
						}
												
						$serviceTask = $builder->serviceTask($id, $el->getAttribute('name'));
						$serviceTask->setDocumentation($builder->stringExp($this->getDocumentation($el)));
						
						break;
					case 'userTask':
						
						$userTask = $builder->userTask($id, $el->getAttribute('name'));
						$userTask->setDocumentation($builder->stringExp($this->getDocumentation($el)));
						
						if($el->hasAttributeNS(self::NS_IMPL, 'assignee') && '' !== trim($el->getAttributeNS(self::NS_IMPL, 'assignee')))
						{
							$userTask->setAssignee($builder->stringExp($el->getAttributeNS(self::NS_IMPL, 'assignee')));
						}
						
						break;
					case 'scriptTask':
						
						$script = '';
						
						foreach($xpath->query('m:script', $el) as $scriptElement)
						{
							$script .= $scriptElement->textContent;
						}
						
						$scriptTask = $builder->scriptTask($id, $el->getAttribute('scriptFormat'), $script, $el->getAttribute('name'));
						$scriptTask->setDocumentation($builder->stringExp($this->getDocumentation($el)));
						
						if($el->hasAttributeNS(self::NS_IMPL, 'resultVariable'))
						{
							$scriptTask->setResultVariable($el->getAttributeNS(self::NS_IMPL, 'resultVariable'));
						}
						
						break;
					case 'sendTask':
						
						$builder->intermediateMessageThrowEvent($id, $el->getAttribute('name'));
						break;
					case 'callActivity':
						
						$call = $builder->callActivity($id, $el->getAttribute('calledElement'), $el->getAttribute('name'));
						$call->setDocumentation($builder->stringExp($this->getDocumentation($el)));
						
						foreach($xpath->query('m:extensionElements/i:in[@source]', $el) as $in)
						{
							$call->addInput($in->getAttribute('target'), $in->getAttribute('source'));
						}
						
						foreach($xpath->query('m:extensionElements/i:in[@sourceExpression]', $el) as $in)
						{
							$call->addInput($in->getAttribute('target'), $builder->exp($in->getAttribute('sourceExpression')));
						}
						
						foreach($xpath->query('m:extensionElements/i:out[@source]', $el) as $out)
						{
							$call->addOutput($out->getAttribute('target'), $out->getAttribute('source'));
						}
						
						foreach($xpath->query('m:extensionElements/i:out[@sourceExpression]', $el) as $out)
						{
							$call->addOutput($out->getAttribute('target'), $builder->exp($out->getAttribute('sourceExpression')));
						}
						
						break;
					case 'subProcess':
						
						$startNodeId = NULL;
						
						foreach($xpath->query('m:startEvent', $el) as $node)
						{
							$startNodeId = (string)$node->getAttribute('id');
						}
						
						$sub = $builder->subProcess($id, $startNodeId, $el->getAttribute('name'));
						
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
						
						$gateway = $builder->exclusiveGateway($id, $el->getAttribute('name'));
						$gateway->setDefaultFlow($el->getAttribute('default'));
						
						break;
					case 'inclusiveGateway':
					
						$gateway = $builder->inclusiveGateway($id, $el->getAttribute('name'));
						$gateway->setDefaultFlow($el->getAttribute('default'));
					
						break;
					case 'parallelGateway':
						
						$builder->parallelGateway($id, $el->getAttribute('name'));
						break;
					case 'eventBasedGateway':
						
						$builder->eventBasedGateway($id, $el->getAttribute('name'));
						break;
					case 'intermediateCatchEvent':
						
						foreach($xpath->query('m:messageEventDefinition', $el) as $messageElement)
						{
							$message = $messages[$messageElement->getAttribute('messageRef')];
							$builder->intermediateMessageCatchEvent($id, $message, $el->getAttribute('name'));
							
							break 2;
						}
						
						foreach($xpath->query('m:signalEventDefinition', $el) as $signalElement)
						{
							$signal = $signals[$signalElement->getAttribute('signalRef')];
							$builder->intermediateSignalCatchEvent($id, $signal, $el->getAttribute('name'));
							
							break 2;
						}
						
						$builder->node($id);
						
						break;
					case 'intermediateThrowEvent':
						
						foreach($xpath->query('m:messageEventDefinition', $el) as $def)
						{
							$builder->intermediateMessageThrowEvent($id, $el->getAttribute('name'));
							
							break 2;
						}
						
						foreach($xpath->query('m:signalEventDefinition', $el) as $def)
						{
							$signal = $signals[$def->getAttribute('signalRef')];
							$builder->intermediateSignalThrowEvent($id, $signal, $el->getAttribute('name'));
								
							break 2;
						}
						
						$builder->node($id);
						
						break;
					case 'boundaryEvent':
						
						$attachedTo = $el->getAttribute('attachedToRef');
						$cancelActivity = true;
						
						if($el->hasAttribute('cancelActivity'))
						{
							$cancelActivity = (strtolower($el->getAttribute('cancelActivity')) == 'true');
						}
						
						foreach($xpath->query('m:messageEventDefinition', $el) as $messageElement)
						{
							$message = $messages[$messageElement->getAttribute('messageRef')];
							$event = $builder->messageBoundaryEvent($id, $attachedTo, $message, $el->getAttribute('name'));
							$event->setInterrupting($cancelActivity);
								
							break 2;
						}
						
						foreach($xpath->query('m:signalEventDefinition', $el) as $def)
						{
							$signal = $signals[$def->getAttribute('signalRef')];
							$event = $builder->signalBoundaryEvent($id, $attachedTo, $signal, $el->getAttribute('name'));
							$event->setInterrupting($cancelActivity);
							
							break 2;
						}
						
						break;
					default:
						$builder->node($id);
				}
			}
		}
		
		if(empty($result))
		{
			throw new \OutOfBoundsException(sprintf('No process definition(s) found'));
		}
		
		return $result;
	}
	
	protected function getDocumentation(\DOMElement $el)
	{
		$docs = [];
		$xpath = $this->createXPath($el->ownerDocument);
		
		foreach($xpath->query('m:documentation', $el) as $doc)
		{
			$docs[] = $doc->textContent;
		}
		
		return empty($docs) ? NULL : implode(' ', $docs);
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
