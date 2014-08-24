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
use KoolKode\BPMN\Task\Event\UserTaskCreatedEvent;
use KoolKode\Util\UUID;

/**
 * Creates a new user task instance.
 * 
 * @author Martin Schröder
 */
class CreateUserTaskCommand extends AbstractBusinessCommand
{
	protected $name;
	
	protected $priority;
	
	protected $dueDate;
	
	protected $execution;
	
	protected $documentation;
	
	public function __construct($name, $priority, VirtualExecution $execution, $documentation = NULL)
	{
		$this->name = (string)$name;
		$this->priority = (int)$priority;
		$this->execution = $execution;
		$this->documentation = ($documentation === NULL) ? NULL : (string)$documentation;
	}
	
	public function setDueDate(\DateTimeInterface $dueDate = NULL)
	{
		if($dueDate === NULL)
		{
			$this->dueDate = NULL;
		}
		else
		{
			$this->dueDate = $dueDate->getTimestamp();
		}
	}
	
	public function executeCommand(ProcessEngine $engine)
	{
		$id = UUID::createRandom();
		$sql = "	INSERT INTO `#__user_task`
						(`id`, `execution_id`, `name`, `documentation`, `activity`, `created_at`, `priority`, `due_at`)
					VALUES
						(:id, :eid, :name, :doc, :activity, :created, :priority, :due)
		";
		$stmt = $engine->prepareQuery($sql);
		$stmt->bindValue('id', $id);
		$stmt->bindValue('eid', $this->execution->getId());
		$stmt->bindValue('name', $this->name);
		$stmt->bindValue('doc', $this->documentation);
		$stmt->bindValue('activity', $this->execution->getNode()->getId());
		$stmt->bindValue('created', time());
		$stmt->bindValue('priority', $this->priority);
		$stmt->bindValue('due', $this->dueDate);
		$stmt->execute();
		
		$engine->debug('Created user task "{task}" with id {id}', [
			'task' => $this->name,
			'id' => (string)$id
		]);
		
		$task = $engine->getTaskService()
					   ->createTaskQuery()
					   ->taskId($id)
					   ->findOne();
		
		$engine->notify(new UserTaskCreatedEvent($task, $engine));
		
		return $task;
	}
}
