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

use KoolKode\BPMN\Delegate\Behavior\DelegateTaskBehavior;
use KoolKode\BPMN\Delegate\Behavior\ExpressionTaskBehavior;
use KoolKode\BPMN\Delegate\Behavior\ScriptTaskBehavior;
use KoolKode\BPMN\Delegate\Behavior\ServiceTaskBehavior;
use KoolKode\BPMN\Runtime\Behavior\CallActivityBehavior;
use KoolKode\BPMN\Runtime\Behavior\EventBasedGatewayBehavior;
use KoolKode\BPMN\Runtime\Behavior\ExclusiveGatewayBehavior;
use KoolKode\BPMN\Runtime\Behavior\InclusiveGatewayBehavior;
use KoolKode\BPMN\Runtime\Behavior\ParallelGatewayBehavior;
use KoolKode\BPMN\Runtime\Behavior\IntermediateMessageCatchBehavior;
use KoolKode\BPMN\Runtime\Behavior\IntermediateMessageThrowBehavior;
use KoolKode\BPMN\Runtime\Behavior\IntermediateSignalCatchBehavior;
use KoolKode\BPMN\Runtime\Behavior\IntermediateSignalThrowBehavior;
use KoolKode\BPMN\Runtime\Behavior\MessageBoundaryEventBehavior;
use KoolKode\BPMN\Runtime\Behavior\MessageStartEventBehavior;
use KoolKode\BPMN\Runtime\Behavior\NoneStartEventBehavior;
use KoolKode\BPMN\Runtime\Behavior\SignalBoundaryEventBehavior;
use KoolKode\BPMN\Runtime\Behavior\SignalStartEventBehavior;
use KoolKode\BPMN\Runtime\Behavior\SubProcessBehavior;
use KoolKode\BPMN\Task\Behavior\UserTaskBehavior;
use KoolKode\Expression\ExpressionInterface;
use KoolKode\Expression\Parser\ExpressionLexer;
use KoolKode\Expression\Parser\ExpressionParser;
use KoolKode\Process\ExpressionTrigger;
use KoolKode\Process\ProcessDefinition;
use KoolKode\Process\ProcessBuilder;

/**
 * Convenient builder that aids during creation of BPMN 2.0 process models.
 * 
 * @author Martin Schröder
 */
class BusinessProcessBuilder
{
	protected $key;
	
	protected $builder;
	
	protected $expressionParser;
	
	public function __construct($key, $title = '', ExpressionParser $parser = NULL)
	{
		$this->key = $key;
		$this->builder = new ProcessBuilder($title);
		
		if($parser === NULL)
		{
			$lexer = new ExpressionLexer();
			$lexer->setDelimiters('#{', '}');
		
			$parser = new ExpressionParser($lexer);
		}
		
		$this->expressionParser = $parser;
	}
	
	public function getKey()
	{
		return $this->key;
	}
	
	public function build()
	{
		return $this->builder->build();
	}
	
	public function node($id)
	{
		return $this->builder->node($id);
	}
	
	public function startEvent($id, $name = NULL)
	{
		$behavior = new NoneStartEventBehavior();
		$behavior->setName($this->stringExp($name));
		
		$this->builder->node($id)->behavior($behavior)->initial();
		
		return $behavior;
	}
	
	public function messageStartEvent($id, $messageName, $name = NULL)
	{
		$behavior = new MessageStartEventBehavior($messageName);
		$behavior->setName($this->stringExp($name));
		
		$this->builder->node($id)->behavior($behavior);
		
		return $behavior;
	}
	
	public function signalStartEvent($id, $signalName, $name = NULL)
	{
		$behavior = new SignalStartEventBehavior($signalName);
		$behavior->setName($this->stringExp($name));
		
		$this->builder->node($id)->behavior($behavior);
		
		return $behavior;
	}
	
	public function endEvent($id)
	{
		return $this->builder->node($id);
	}
	
	public function messageEndEvent($id, $name = NULL)
	{
		$behavior = new IntermediateMessageThrowBehavior();
		$behavior->setName($this->stringExp($name));
		
		$this->builder->node($id)->behavior($behavior);
		
		return $behavior;
	}
	
	public function signalEndEvent($id, $signalName, $name = NULL)
	{
		$behavior = new IntermediateSignalThrowBehavior($signalName);
		$behavior->setName($this->stringExp($name));
		
		$this->builder->node($id)->behavior($behavior);
		
		return $behavior;
	}
	
	public function sequenceFlow($id, $from, $to, $condition = NULL)
	{
		$transition = $this->builder->transition($id, $from, $to);
		
		if($condition !== NULL)
		{
			$transition->trigger(new ExpressionTrigger($this->exp($condition)));
		}
		
		return $transition;
	}
	
	public function exclusiveGateway($id, $name = NULL)
	{
		$behavior = new ExclusiveGatewayBehavior();
		$behavior->setName($this->stringExp($name));
		
		$this->builder->node($id)->behavior($behavior);
		
		return $behavior;
	}
	
	public function inclusiveGateway($id, $name = NULL)
	{
		$behavior = new InclusiveGatewayBehavior();
		$behavior->setName($this->stringExp($name));
		
		$this->builder->node($id)->behavior($behavior);
		
		return $behavior;
	}
	
	public function parallelGateway($id, $name = NULL)
	{
		$behavior = new ParallelGatewayBehavior();
		$behavior->setName($this->stringExp($name));
		
		$this->builder->node($id)->behavior($behavior);
		
		return $behavior;
	}
	
	public function eventBasedGateway($id, $name = NULL)
	{
		$behavior = new EventBasedGatewayBehavior();
		$behavior->setName($this->stringExp($name));
		
		$this->builder->node($id)->behavior($behavior);
		
		return $behavior;
	}
	
	public function serviceTask($id, $name = NULL)
	{
		$behavior = new ServiceTaskBehavior();
		$behavior->setName($this->stringExp($name));
		
		$this->builder->node($id)->behavior($behavior);
		
		return $behavior;
	}
	
	public function delegateTask($id, $typeName, $name = NULL)
	{
		$behavior = new DelegateTaskBehavior($this->stringExp($typeName));
		$behavior->setName($this->stringExp($name));
		
		$this->builder->node($id)->behavior($behavior);
		
		return $behavior;
	}
	
	public function expressionTask($id, $expression, $name = NULL)
	{
		$behavior = new ExpressionTaskBehavior($this->exp($expression));
		$behavior->setName($this->stringExp($name));
		
		$this->builder->node($id)->behavior($behavior);
		
		return $behavior;
	}
	
	public function userTask($id, $name = NULL)
	{
		$behavior = new UserTaskBehavior();
		$behavior->setName($this->stringExp($name));
		
		$this->builder->node($id)->behavior($behavior);
		
		return $behavior;
	}
	
	public function scriptTask($id, $language, $script, $name = NULL)
	{
		$behavior = new ScriptTaskBehavior(strtolower($language), $script);
		$behavior->setName($this->stringExp($name));
		
		$this->builder->node($id)->behavior($behavior);
		
		return $behavior;
	}
	
	public function callActivity($id, $element, $name = NULL)
	{
		$behavior = new CallActivityBehavior($element);
		$behavior->setName($this->stringExp($name));
		
		$this->builder->node($id)->behavior($behavior);
		
		return $behavior;
	}
	
	public function subProcess($id, $startNodeId, $name = NULL)
	{
		$behavior = new SubProcessBehavior($startNodeId);
		$behavior->setName($this->stringExp($name));
		
		$this->builder->node($id)->behavior($behavior);
		
		return $behavior;
	}
	
	public function intermediateSignalCatchEvent($id, $signal, $name = NULL)
	{
		$behavior = new IntermediateSignalCatchBehavior($signal);
		$behavior->setName($this->stringExp($name));
		
		$this->builder->node($id)->behavior($behavior);
		
		return $behavior;
	}
	
	public function intermediateMessageCatchEvent($id, $message, $name = NULL)
	{
		$behavior = new IntermediateMessageCatchBehavior($message);
		$behavior->setName($this->stringExp($name));
		
		$this->builder->node($id)->behavior($behavior);
		
		return $behavior;
	}
	
	public function intermediateSignalThrowEvent($id, $signal, $name = NULL)
	{
		$behavior = new IntermediateSignalThrowBehavior($signal);
		$behavior->setName($this->stringExp($name));
		
		$this->builder->node($id)->behavior($behavior);
		
		return $behavior;
	}
	
	public function intermediateMessageThrowEvent($id, $name = NULL)
	{
		$behavior = new IntermediateMessageThrowBehavior();
		$behavior->setName($this->stringExp($name));
		
		$this->builder->node($id)->behavior($behavior);
		
		return $behavior;
	}
	
	public function signalBoundaryEvent($id, $attachedTo, $signal, $name = NULL)
	{
		$behavior = new SignalBoundaryEventBehavior($attachedTo, $signal);
		$behavior->setName($this->stringExp($name));
		
		$this->builder->node($id)->behavior($behavior);
		
		return $behavior;
	}
	
	public function messageBoundaryEvent($id, $attachedTo, $message, $name = NULL)
	{
		$behavior = new MessageBoundaryEventBehavior($attachedTo, $message);
		$behavior->setName($this->stringExp($name));
		
		$this->builder->node($id)->behavior($behavior);
		
		return $behavior;
	}
	
	public function normalize($input)
	{
		return trim(preg_replace("'\s+'", ' ', $input));
	}
	
	public function exp($input)
	{
		return ($input === NULL) ? NULL : $this->expressionParser->parse($this->normalize($input));
	}
	
	public function stringExp($input)
	{
		return ($input === NULL) ? NULL : $this->expressionParser->parseString($this->normalize($input));
	}
}
