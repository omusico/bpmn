<?php

/*
 * This file is part of KoolKode BPMN.
*
* (c) Martin Schröder <m.schroeder2007@gmail.com>
*
* For the full copyright and license information, please view the LICENSE
* file that was distributed with this source code.
*/

namespace KoolKode\BPMN\Delegate\Behavior;

use KoolKode\BPMN\Delegate\DelegateExecution;
use KoolKode\BPMN\Delegate\DelegateTaskInterface;
use KoolKode\BPMN\Delegate\Event\TaskExecutedEvent;
use KoolKode\BPMN\Engine\AbstractScopeBehavior;
use KoolKode\BPMN\Engine\VirtualExecution;
use KoolKode\BPMN\Runtime\Command\SignalExecutionCommand;
use KoolKode\Expression\ExpressionInterface;

/**
 * Connects a custom class implementing DelegateTaskInterface to a node in a BPMN process.
 * 
 * @author Martin Schröder
 */
class DelegateTaskBehavior extends AbstractScopeBehavior
{
	protected $typeName;
	
	public function __construct(ExpressionInterface $typeName)
	{
		$this->typeName = $typeName;
	}
	
	public function executeBehavior(VirtualExecution $execution)
	{
		$this->createScopedEventSubscriptions($execution);
		
		$engine = $execution->getEngine();
		$typeName = $this->getStringValue($this->typeName, $execution->getExpressionContext());
		$name = $this->getStringValue($this->name, $execution->getExpressionContext());
		
		$task = $engine->createDelegateTask($typeName);
		
		if(!$task instanceof DelegateTaskInterface)
		{
			throw new \RuntimeException('Invalid service task implementation: ' . get_class($task));
		}
		
		$engine->debug('Execute delegate task "{task}" implemented by <{class}>', [
			'task' => $name,
			'class' => get_class($task)
		]);
		
		$task->execute(new DelegateExecution($execution));
		
		$engine->notify(new TaskExecutedEvent($name, new DelegateExecution($execution), $engine));
		$engine->pushCommand(new SignalExecutionCommand($execution));
		
		$execution->waitForSignal();
	}
}
