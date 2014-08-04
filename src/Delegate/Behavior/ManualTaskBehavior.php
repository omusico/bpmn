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
use KoolKode\BPMN\Delegate\Event\ManualTaskExecutedEvent;
use KoolKode\BPMN\Engine\AbstractScopeBehavior;
use KoolKode\BPMN\Engine\VirtualExecution;
use KoolKode\BPMN\Runtime\Command\SignalExecutionCommand;

/**
 * Handles manual tasks that have no implementation in code.
 * 
 * @author Martin Schröder
 */
class ManualTaskBehavior extends AbstractScopeBehavior
{
	public function executeBehavior(VirtualExecution $execution)
	{
		$name = $this->getStringValue($this->name, $execution->getExpressionContext());
		
		$execution->getEngine()->debug('Executing manual task "{task}"', [
			'task' => $name
		]);
		
		$execution->getEngine()->notify(new ManualTaskExecutedEvent(
			$name,
			new DelegateExecution($execution),
			$execution->getEngine()
		));
		
		$execution->getEngine()->pushCommand(new SignalExecutionCommand($execution));
		$execution->waitForSignal();
	}
}
