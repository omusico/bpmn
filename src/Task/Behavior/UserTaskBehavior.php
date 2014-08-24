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
	
	protected $priority;
	
	protected $dueDate;
	
	public function setAssignee(ExpressionInterface $assignee = NULL)
	{
		$this->assignee = $assignee;
	}
	
	public function setPriority(ExpressionInterface $priority = NULL)
	{
		$this->priority = $priority;
	}
	
	public function setDueDate(ExpressionInterface $dueDate = NULL)
	{
		$this->dueDate = $dueDate;
	}
	
	public function executeBehavior(VirtualExecution $execution)
	{
		$this->createScopedEventSubscriptions($execution);
		
		$context = $execution->getExpressionContext();
		$command = new CreateUserTaskCommand(
			$this->getStringValue($this->name, $context),
			(int)$this->getIntegerValue($this->priority, $context),
			$execution,
			$this->getStringValue($this->documentation, $context)
		);
		
		if(NULL !== ($due = $this->getDateValue($this->dueDate, $context)))
		{
			$command->setDueDate($due);
		}
		
		$task = $execution->getEngine()->executeCommand($command);
		
		if($this->assignee !== NULL)
		{
			$execution->getEngine()->pushCommand(new ClaimUserTaskCommand(
				$task->getId(),
				$this->getStringValue($this->assignee, $context)
			));
		}
		
		$execution->waitForSignal();
	}
	
	public function interruptBehavior(VirtualExecution $execution)
	{
		$execution->getEngine()->executeCommand(new RemoveUserTaskCommand($execution));
	}
}
