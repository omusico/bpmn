<?php

/*
 * This file is part of KoolKode BPMN.
*
* (c) Martin Schröder <m.schroeder2007@gmail.com>
*
* For the full copyright and license information, please view the LICENSE
* file that was distributed with this source code.
*/

namespace KoolKode\BPMN\Task\Command;

use KoolKode\BPMN\Engine\AbstractBusinessCommand;
use KoolKode\BPMN\Engine\ProcessEngine;
use KoolKode\BPMN\Engine\VirtualExecution;

/**
 * Deletes a user task from the task list.
 * 
 * @author Martin Schröder
 */
class RemoveUserTaskCommand extends AbstractBusinessCommand
{
	protected $execution;
	
	public function __construct(VirtualExecution $execution)
	{
		$this->execution = $execution;
	}
	
	public function executeCommand(ProcessEngine $engine)
	{
		$task = $engine->getTaskService()
					   ->createTaskQuery()
					   ->executionId($this->execution->getId())
					   ->findOne();
		
		$sql = "	DELETE FROM `#__user_task`
					WHERE `execution_id` = :eid
		";
		$stmt = $engine->prepareQuery($sql);
		$stmt->bindValue('eid', $this->execution->getId());
		$stmt->execute();
		
		$engine->debug('Removed user task "{task}" with id {id}', [
			'task' => $task->getName(),
			'id' => (string)$task->getId()
		]);
	}
}