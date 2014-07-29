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
use KoolKode\Expression\ExpressionInterface;

/**
 * Handles service tasks without specific implementation.
 * 
 * @author Martin Schröder
 */
class ServiceTaskBehavior extends AbstractScopeBehavior
{
	protected $name;
	
	public function __construct(ExpressionInterface $name)
	{
		$this->name = $name;
	}
	
	public function executeBehavior(VirtualExecution $execution)
	{
		$execution->getEngine()->debug('Executing service task "{task}"', [
			'task' => (string)call_user_func($this->name, $execution->getExpressionContext())
		]);
		
		$execution->getEngine()->notify(new ServiceTaskExecutedEvent(
			new DelegateExecution($execution),
			$execution->getEngine()
		));
		
		$execution->getEngine()->pushCommand(new SignalExecutionCommand($execution));
		$execution->waitForSignal();
	}
}
