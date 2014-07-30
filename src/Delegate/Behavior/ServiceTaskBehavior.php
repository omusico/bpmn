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
use KoolKode\BPMN\Delegate\Event\ServiceTaskExecutedEvent;
use KoolKode\BPMN\Engine\AbstractScopeBehavior;
use KoolKode\BPMN\Engine\VirtualExecution;
use KoolKode\BPMN\Runtime\Command\SignalExecutionCommand;

/**
 * Handles service tasks without specific implementation.
 * 
 * @author Martin Schröder
 */
class ServiceTaskBehavior extends AbstractScopeBehavior
{
	public function executeBehavior(VirtualExecution $execution)
	{
		$execution->getEngine()->debug('Executing service task "{task}"', [
			'task' => $this->getStringValue($this->name, $execution->getExpressionContext())
		]);
		
		$execution->getEngine()->notify(new ServiceTaskExecutedEvent(
			new DelegateExecution($execution),
			$execution->getEngine()
		));
		
		$execution->getEngine()->pushCommand(new SignalExecutionCommand($execution));
		$execution->waitForSignal();
	}
}
