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
use KoolKode\BPMN\Delegate\Event\TaskExecutedEvent;
use KoolKode\BPMN\Engine\AbstractScopeBehavior;
use KoolKode\BPMN\Engine\VirtualExecution;
use KoolKode\BPMN\Runtime\Command\SignalExecutionCommand;

/**
 * Generic task behavior that triggers an event and proceeds with the process.
 * 
 * @author Martin Schröder
 */
class TaskBehavior extends AbstractScopeBehavior
{
	public function executeBehavior(VirtualExecution $execution)
	{
		$this->createScopedEventSubscriptions($execution);
		
		$engine = $execution->getEngine();
		$name = $this->getStringValue($this->name, $execution->getExpressionContext());
		
		$execution->getEngine()->debug('Executing manual task "{task}"', [
			'task' => $name
		]);
		
		$engine->notify(new TaskExecutedEvent($name, new DelegateExecution($execution), $engine));
		$engine->pushCommand(new SignalExecutionCommand($execution));
		
		$execution->waitForSignal();
	}
}
