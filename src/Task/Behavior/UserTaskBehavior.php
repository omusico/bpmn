<?php

/*
 * This file is part of KoolKode BPMN.
*
* (c) Martin Schröder <m.schroeder2007@gmail.com>
*
* For the full copyright and license information, please view the LICENSE
* file that was distributed with this source code.
*/

namespace KoolKode\BPMN\Task\Behavior;

use KoolKode\BPMN\Engine\AbstractScopeBehavior;
use KoolKode\BPMN\Engine\VirtualExecution;
use KoolKode\BPMN\Task\Command\ClaimUserTaskCommand;
use KoolKode\BPMN\Task\Command\CreateUserTaskCommand;
use KoolKode\BPMN\Task\Command\RemoveUserTaskCommand;
use KoolKode\Expression\ExpressionInterface;

/**
 * Creates user tasks and waits for their completion.
 * 
 * @author Martin Schröder
 */
class UserTaskBehavior extends AbstractScopeBehavior
{
	protected $assignee;
	
	public function setAssignee(ExpressionInterface $assignee = NULL)
	{
		$this->assignee = $assignee;
	}
	
	public function executeBehavior(VirtualExecution $execution)
	{
		$name = $this->getStringValue($this->name, $execution->getExpressionContext());
		
		$task = $execution->getEngine()->executeCommand(new CreateUserTaskCommand($name, $execution));
		
		if($this->assignee !== NULL)
		{
			$execution->getEngine()->pushCommand(new ClaimUserTaskCommand(
				$task->getId(),
				$this->getStringValue($this->assignee, $execution->getExpressionContext())
			));
		}
		
		$execution->waitForSignal();
	}
	
	public function interruptBehavior(VirtualExecution $execution)
	{
		$execution->getEngine()->executeCommand(new RemoveUserTaskCommand($execution));
	}
}
