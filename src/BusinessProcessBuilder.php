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

use KoolKode\Expression\ExpressionInterface;
use KoolKode\Expression\Parser\ExpressionLexer;
use KoolKode\Expression\Parser\ExpressionParser;
use KoolKode\Process\Behavior\ExclusiveChoiceBehavior;
use KoolKode\Process\Behavior\InlusiveChoiceBehavior;
use KoolKode\Process\Behavior\SyncBehavior;
use KoolKode\Process\ExpressionTrigger;
use KoolKode\Process\ProcessDefinition;
use KoolKode\Process\ProcessBuilder;
use KoolKode\Util\UUID;

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
	
	public function __construct($key, $title = '')
	{
		$this->key = $key;
		
		$lexer = new ExpressionLexer();
		$lexer->setDelimiters('#{', '}');
		
		$this->builder = new ProcessBuilder($title);
		$this->expressionParser = new ExpressionParser($lexer);
	}
	
	public function getKey()
	{
		return $this->key;
	}
	
	public function build()
	{
		return $this->builder->build();
	}
	
	public function startEvent($id)
	{
		return $this->builder->node($id)->initial();
	}
	
	public function messageStartEvent($id, $messageName)
	{
		return $this->builder->node($id)->behavior(new Runtime\Behavior\MessageStartEventBehavior($messageName));
	}
	
	public function endEvent($id)
	{
		return $this->builder->node($id);
	}
	
	public function sequenceFlow($id, $from, $to, $condition = NULL)
	{
		$transition = $this->builder->transition($id, $from, $to);
		
		if($condition !== NULL)
		{
			if($condition instanceof ExpressionInterface)
			{
				$transition->trigger(new ExpressionTrigger($condition));
			}
			else
			{
				$transition->trigger(new ExpressionTrigger($this->expressionParser->parse($condition)));
			}
		}
		
		return $transition;
	}
	
	public function exclusiveGateway($id, $defaultFlow = NULL)
	{
		return $this->builder->node($id)->behavior(new ExclusiveChoiceBehavior($defaultFlow));
	}
	
	public function inclusiveGateway($id, $defaultFlow = NULL)
	{
		return $this->builder->node($id)->behavior(new InclusiveChoiceBehavior($defaultFlow));
	}
	
	public function parallelGateway($id)
	{
		return $this->builder->node($id)->behavior(new SyncBehavior());
	}
	
	public function serviceTask($id, $name = '')
	{
		return $this->builder->node($id);
	}
	
	public function delegateTask($id, $typeName, $name = '')
	{
		return $this->builder->node($id)->behavior(new Delegate\Behavior\DelegateTaskBehavior($typeName, $name));
	}
	
	public function userTaks($id, $name = '')
	{
		return $this->builder->node($id)->behavior(new Task\Behavior\UserTaskBehavior($this->normalize($name)));
	}
	
	public function scriptTask($id, $language, $script, $name = '')
	{
		return $this->builder->node($id)->behavior(new Task\Behavior\ScriptTaskBehavior($language, $script, $name));
	}
	
	public function intermediateSignalCatchEvent($id, $signal)
	{
		return $this->builder->node($id)->behavior(new Runtime\Behavior\IntermediateSignalCatchBehavior($signal));
	}
	
	public function intermediateMessageCatchEvent($id, $message)
	{
		return $this->builder->node($id)->behavior(new Runtime\Behavior\IntermediateMessageCatchBehavior($message));
	}
	
	public function intermediateSignalThrowEvent($id, $signal)
	{
		return $this->builder->node($id)->behavior(new Runtime\Behavior\IntermediateSignalThrowBehavior($signal));
	}
	
	public function intermediateMessageThrowEvent($id, $name = '')
	{
		return $this->builder->node($id)->behavior(new Runtime\Behavior\IntermediateMessageThrowBehavior($name));
	}
	
	protected function normalize($input)
	{
		return trim(preg_replace("'\s+'", ' ', $input));
	}
}
