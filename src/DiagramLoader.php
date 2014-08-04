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
	
	protected $xpath;
	
	protected $signals = [];
	
	protected $messages = [];
	
	public function parseDiagramFile($file)
	{
		return $this->parseDiagram((new XmlDocumentBuilder())->buildDocument(new \SplFileInfo($file)));
	}
	
	public function parseDiagram(\DOMDocument $xml)
	{
		try
		{
			$this->xpath = $this->createXPath($xml);
			
			foreach($this->xpath->query('/m:definitions/m:message[@id][@name]') as $messageElement)
			{
				$this->messages[trim($messageElement->getAttribute('id'))] = trim($messageElement->getAttribute('name'));
			}
			
			foreach($this->xpath->query('/m:definitions/m:signal[@id][@name]') as $signalElement)
			{
				$this->signals[trim($signalElement->getAttribute('id'))] = trim($signalElement->getAttribute('name'));
			}
			
			$result = [];
			
			foreach($this->xpath->query('/m:definitions/m:process[@id]') as $processElement)
			{
				if('true' === strtolower($processElement->getAttribute('isExecutable')))
				{	
					$result[] = $this->parseProcessDefinition($processElement);
				}
			}
			
			if(empty($result))
			{
				throw new \RuntimeException('No executable process definitions found');
			}
			
			return $result;
		}
		finally
		{
			$this->xpath = NULL;
			$this->signals = [];
			$this->messages = [];
		}
	}
	
	protected function parseProcessDefinition(\DOMElement $process)
	{
		$title = $process->hasAttribute('name') ? trim($process->getAttribute('name')) : '';
		$builder = new BusinessProcessBuilder($process->getAttribute('id'), $title);
		
		foreach($this->xpath->query('m:*[@id]', $process) as $element)
		{
			$this->parseElement($element, $builder);
		}
		
		return $builder;
	}
	
	protected function parseElement(\DOMElement $el, BusinessProcessBuilder $builder)
	{
		$id = $el->getAttribute('id');
		
		switch($el->localName)
		{
			case 'sequenceFlow':
				return $this->parseSequenceFlow($id, $el, $builder);
			case 'serviceTask':
				return $this->parseServiceTask($id, $el, $builder);
			case 'scriptTask':
				return $this->parseScriptTask($id, $el, $builder);
			case 'userTask':
				return $this->parseUserTask($id, $el, $builder);
			case 'manualTask':
				return $this->parseManualTask($id, $el, $builder);
			case 'sendTask':
				return $this->parseSendTask($id, $el, $builder);
			case 'callActivity':
				return $this->parseCallActivity($id, $el, $builder);
			case 'subProcess':
				return $this->parseSubProcess($id, $el, $builder);
			case 'boundaryEvent':
				return $this->parseBoundaryEvent($id, $el, $builder);
			case 'startEvent':
				return $this->parseStartEvent($id, $el, $builder);
			case 'endEvent':
				return $this->parseEndEvent($id, $el, $builder);
			case 'intermediateCatchEvent':
				return $this->parseIntermediateCatchEvent($id, $el, $builder);
			case 'intermediateThrowEvent':
				return $this->parseIntermediateThrowEvent($id, $el, $builder);
			case 'exclusiveGateway':
				return $this->parseExclusiveGateway($id, $el, $builder);
			case 'inclusiveGateway':
				return $this->parseInclusiveGateway($id, $el, $builder);
			case 'parallelGateway':
				return $this->parseParallelGateway($id, $el, $builder);
			case 'eventBasedGateway':
				return $this->parseEventBasedGateway($id, $el, $builder);
		}
	}
	
	protected function parseSequenceFlow($id, \DOMElement $el, BusinessProcessBuilder $builder)
	{
		$condition = NULL;
		
		foreach($this->xpath->query('m:conditionExpression', $el) as $conditionElement)
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
		
		return $builder->sequenceFlow($id, $el->getAttribute('sourceRef'), $el->getAttribute('targetRef'), $condition);
	}
	
	protected function parseServiceTask($id, \DOMElement $el, BusinessProcessBuilder $builder)
	{
		if($el->hasAttributeNS(self::NS_IMPL, 'class') && '' !== trim($el->getAttributeNS(self::NS_IMPL, 'class')))
		{
			$delegateTask = $builder->delegateTask($id, $el->getAttributeNS(self::NS_IMPL, 'class'), $el->getAttribute('name'));
			$delegateTask->setDocumentation($builder->stringExp($this->getDocumentation($el)));
				
			return $delegateTask;
		}
		
		if($el->hasAttributeNS(self::NS_IMPL, 'expression') && '' !== $el->getAttributeNS(self::NS_IMPL, 'expression'))
		{
			$expressionTask = $builder->expressionTask($id, $el->getAttributeNS(self::NS_IMPL, 'expression'), $el->getAttribute('name'));
			$expressionTask->setDocumentation($builder->stringExp($this->getDocumentation($el)));
				
			if($el->hasAttributeNS(self::NS_IMPL, 'resultVariable'))
			{
				$expressionTask->setResultVariable($el->getAttributeNS(self::NS_IMPL, 'resultVariable'));
			}
				
			return $expressionTask;
		}
		
		$serviceTask = $builder->serviceTask($id, $el->getAttribute('name'));
		$serviceTask->setDocumentation($builder->stringExp($this->getDocumentation($el)));
		
		return $serviceTask;
	}
	
	protected function parseScriptTask($id, \DOMElement $el, BusinessProcessBuilder $builder)
	{
		$script = '';
		
		foreach($this->xpath->query('m:script', $el) as $scriptElement)
		{
			$script .= $scriptElement->textContent;
		}
		
		$scriptTask = $builder->scriptTask($id, $el->getAttribute('scriptFormat'), $script, $el->getAttribute('name'));
		$scriptTask->setDocumentation($builder->stringExp($this->getDocumentation($el)));
		
		if($el->hasAttributeNS(self::NS_IMPL, 'resultVariable'))
		{
			$scriptTask->setResultVariable($el->getAttributeNS(self::NS_IMPL, 'resultVariable'));
		}
		
		return $scriptTask;
	}
	
	protected function parseUserTask($id, \DOMElement $el, BusinessProcessBuilder $builder)
	{
		$userTask = $builder->userTask($id, $el->getAttribute('name'));
		$userTask->setDocumentation($builder->stringExp($this->getDocumentation($el)));
		
		if($el->hasAttributeNS(self::NS_IMPL, 'assignee') && '' !== trim($el->getAttributeNS(self::NS_IMPL, 'assignee')))
		{
			$userTask->setAssignee($builder->stringExp($el->getAttributeNS(self::NS_IMPL, 'assignee')));
		}
		
		return $userTask;
	}
	
	protected function parseManualTask($id, \DOMElement $el, BusinessProcessBuilder $builder)
	{
		$manualTask = $builder->manualTask($id, $el->getAttribute('name'));
		$manualTask->setDocumentation($builder->stringExp($this->getDocumentation($el)));
	
		return $manualTask;
	}
	
	protected function parseSendTask($id, \DOMElement $el, BusinessProcessBuilder $builder)
	{
		return $builder->intermediateMessageThrowEvent($id, $el->getAttribute('name'));
	}
	
	protected function parseCallActivity($id, \DOMElement $el, BusinessProcessBuilder $builder)
	{
		$call = $builder->callActivity($id, $el->getAttribute('calledElement'), $el->getAttribute('name'));
		$call->setDocumentation($builder->stringExp($this->getDocumentation($el)));
		
		foreach($this->xpath->query('m:extensionElements/i:in[@source]', $el) as $in)
		{
			$call->addInput($in->getAttribute('target'), $in->getAttribute('source'));
		}
		
		foreach($this->xpath->query('m:extensionElements/i:in[@sourceExpression]', $el) as $in)
		{
			$call->addInput($in->getAttribute('target'), $builder->exp($in->getAttribute('sourceExpression')));
		}
		
		foreach($this->xpath->query('m:extensionElements/i:out[@source]', $el) as $out)
		{
			$call->addOutput($out->getAttribute('target'), $out->getAttribute('source'));
		}
		
		foreach($this->xpath->query('m:extensionElements/i:out[@sourceExpression]', $el) as $out)
		{
			$call->addOutput($out->getAttribute('target'), $builder->exp($out->getAttribute('sourceExpression')));
		}
		
		return $call;
	}
	
	protected function parseSubProcess($id, \DOMElement $el, BusinessProcessBuilder $builder)
	{
		// TODO: Find a way to transform event sub process to boundary events or event subscribers.
		
		$triggeredByEvent = ('true' === strtolower($el->getAttribute('triggeredByEvent')));
		$startNodeId = NULL;
		
		foreach($this->xpath->query('m:startEvent', $el) as $node)
		{
			$startNodeId = (string)$node->getAttribute('id');
		}
		
		$sub = $builder->subProcess($id, $startNodeId, $el->getAttribute('name'));
		
		$inner = $this->parseProcessDefinition($el);
		
		$builder->append($inner);
		
		return $sub;
	}
	
	protected function parseStartEvent($id, \DOMElement $el, BusinessProcessBuilder $builder)
	{
		foreach($this->xpath->query('m:messageEventDefinition', $el) as $messageElement)
		{
			$message = $this->messages[$messageElement->getAttribute('messageRef')];
			
			return $builder->messageStartEvent($id, $message, $el->getAttribute('name'));
		}
		
		foreach($this->xpath->query('m:signalEventDefinition', $el) as $signalElement)
		{
			$signal = $this->signals[$signalElement->getAttribute('signalRef')];
			
			return $builder->signalStartEvent($id, $signal, $el->getAttribute('name'));
		}
		
		return $builder->startEvent($id, $el->getAttribute('name'));
	}
	
	protected function parseEndEvent($id, \DOMElement $el, BusinessProcessBuilder $builder)
	{
		foreach($this->xpath->query('m:terminateEventDefinition', $el) as $def)
		{
			return $builder->terminateEndEvent($id, $el->getAttribute('name'));
		}
		
		foreach($this->xpath->query('m:messageEventDefinition', $el) as $def)
		{
			return $builder->messageEndEvent($id, $el->getAttribute('name'));
		}
		
		foreach($this->xpath->query('m:signalEventDefinition', $el) as $def)
		{
			$signal = $this->signals[$def->getAttribute('signalRef')];
			
			return $builder->signalEndEvent($id, $signal, $el->getAttribute('name'));
		}
		
		return $builder->endEvent($id, $el->getAttribute('name'));
	}
	
	protected function parseIntermediateCatchEvent($id, \DOMElement $el, BusinessProcessBuilder $builder)
	{
		foreach($this->xpath->query('m:messageEventDefinition', $el) as $messageElement)
		{
			$message = $this->messages[$messageElement->getAttribute('messageRef')];
			
			return $builder->intermediateMessageCatchEvent($id, $message, $el->getAttribute('name'));
		}
		
		foreach($this->xpath->query('m:signalEventDefinition', $el) as $signalElement)
		{
			$signal = $this->signals[$signalElement->getAttribute('signalRef')];
			
			return $builder->intermediateSignalCatchEvent($id, $signal, $el->getAttribute('name'));
		}
		
		return $builder->intermediateNoneEvent($id, $el->getAttribute('name'));
	}
	
	protected function parseIntermediateThrowEvent($id, \DOMElement $el, BusinessProcessBuilder $builder)
	{
		foreach($this->xpath->query('m:messageEventDefinition', $el) as $def)
		{
			return $builder->intermediateMessageThrowEvent($id, $el->getAttribute('name'));
		}
		
		foreach($this->xpath->query('m:signalEventDefinition', $el) as $def)
		{
			$signal = $this->signals[$def->getAttribute('signalRef')];
			
			return $builder->intermediateSignalThrowEvent($id, $signal, $el->getAttribute('name'));
		}
		
		return $builder->intermediateNoneEvent($id, $el->getAttribute('name'));
	}
	
	protected function parseBoundaryEvent($id, \DOMElement $el, BusinessProcessBuilder $builder)
	{
		$attachedTo = $el->getAttribute('attachedToRef');
		$cancelActivity = true;
		
		if($el->hasAttribute('cancelActivity'))
		{
			$cancelActivity = (strtolower($el->getAttribute('cancelActivity')) == 'true');
		}
		
		foreach($this->xpath->query('m:messageEventDefinition', $el) as $messageElement)
		{
			$message = $this->messages[$messageElement->getAttribute('messageRef')];
			
			$event = $builder->messageBoundaryEvent($id, $attachedTo, $message, $el->getAttribute('name'));
			$event->setInterrupting($cancelActivity);
		
			return $event;
		}
		
		foreach($this->xpath->query('m:signalEventDefinition', $el) as $def)
		{
			$signal = $this->signals[$def->getAttribute('signalRef')];
			
			$event = $builder->signalBoundaryEvent($id, $attachedTo, $signal, $el->getAttribute('name'));
			$event->setInterrupting($cancelActivity);
				
			return $event;
		}
		
		throw new \RuntimeException('Unsupported boundary event type with id ' . $id);
	}
	
	protected function parseExclusiveGateway($id, \DOMElement $el, BusinessProcessBuilder $builder)
	{
		$gateway = $builder->exclusiveGateway($id, $el->getAttribute('name'));
		$gateway->setDefaultFlow($el->getAttribute('default'));
		
		return $gateway;
	}
	
	protected function parseInclusiveGateway($id, \DOMElement $el, BusinessProcessBuilder $builder)
	{		
		$gateway = $builder->inclusiveGateway($id, $el->getAttribute('name'));
		$gateway->setDefaultFlow($el->getAttribute('default'));
		
		return $gateway;
	}
	
	protected function parseParallelGateway($id, \DOMElement $el, BusinessProcessBuilder $builder)
	{
		return $builder->parallelGateway($id, $el->getAttribute('name'));
	}
	
	protected function parseEventBasedGateway($id, \DOMElement $el, BusinessProcessBuilder $builder)
	{
		return $builder->eventBasedGateway($id, $el->getAttribute('name'));
	}
	
	protected function getDocumentation(\DOMElement $el)
	{
		$docs = [];
		
		foreach($this->xpath->query('m:documentation', $el) as $doc)
		{
			$docs[] = $doc->textContent;
		}
		
		return empty($docs) ? NULL : implode(' ', $docs);
	}
	
	protected function createXPath(\DOMNode $xml)
	{
		$xpath = new \DOMXPath(($xml instanceof \DOMDocument) ? $xml : $xml->ownerDocument);
		$xpath->registerNamespace('m', self::NS_MODEL);
		$xpath->registerNamespace('di', self::NS_DI);
		$xpath->registerNamespace('dc', self::NS_DC);
		$xpath->registerNamespace('xsi', self::NS_XSI);
		$xpath->registerNamespace('i', self::NS_IMPL);
		
		return $xpath;
	}
}
