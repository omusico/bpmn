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

use KoolKode\BPMN\Engine\AbstractSignalableBehavior;
use KoolKode\BPMN\Engine\VirtualExecution;
use KoolKode\BPMN\Task\Command\CreateUserTaskCommand;
use KoolKode\Expression\ExpressionInterface;
use KoolKode\BPMN\Task\Command\ClaimUserTaskCommand;

class UserTaskBehavior extends AbstractSignalableBehavior
{
	protected $name;
	
	protected $assignee;
	
	public function __construct(ExpressionInterface $name)
	{
		$this->name = $name;
	}
	
	public function setAssignee(ExpressionInterface $assignee = NULL)
	{
		$this->assignee = $assignee;
	}
	
	public function executeBehavior(VirtualExecution $execution)
	{
		$name = (string)call_user_func($this->name, $execution->getExpressionContext());
		
		$execution->waitForSignal();
		$task = $execution->getEngine()->executeCommand(new CreateUserTaskCommand($name, $execution));
		
		if($this->assignee !== NULL)
		{
			$execution->getEngine()->pushCommand(new ClaimUserTaskCommand(
				$task->getId(),
				call_user_func($this->assignee, $execution->getExpressionContext())
			));
		}
	}
}
