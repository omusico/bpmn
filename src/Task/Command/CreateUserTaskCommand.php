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

class CreateUserTaskCommand extends AbstractBusinessCommand
{
	protected $name;
	
	protected $execution;
	
	public function __construct($name, VirtualExecution $execution)
	{
		$this->name = (string)$name;
		$this->execution = $execution;
	}
	
	public function executeCommand(ProcessEngine $engine)
	{
		$id = UUID::createRandom();
		$sql = "	INSERT INTO `#__bpm_user_task`
						(`id`, `execution_id`, `name`, `activity`, `created_at`)
					VALUES
						(:id, :eid, :name, :activity, :created)
		";
		$stmt = $engine->prepareQuery($sql);
		$stmt->bindValue('id', $id->toBinary());
		$stmt->bindValue('eid', $this->execution->getId()->toBinary());
		$stmt->bindValue('name', $this->name);
		$stmt->bindValue('activity', $this->execution->getNode()->getId());
		$stmt->bindValue('created', time());
		$stmt->execute();
		
		$engine->debug('Created user task {task} with id {id}', [
			'task' => $this->name,
			'id' => (string)$id
		]);
		
		$task = $engine->getTaskService()
					   ->createTaskQuery()
					   ->taskId($id)
					   ->findOne();
		
		$engine->notify(new UserTaskCreatedEvent($task, $engine));
	}
}
