<?php

/*
 * This file is part of KoolKode BPMN.
*
* (c) Martin Schröder <m.schroeder2007@gmail.com>
*
* For the full copyright and license information, please view the LICENSE
* file that was distributed with this source code.
*/

namespace KoolKode\BPMN\Command;

use KoolKode\BPMN\CommandContext;
use KoolKode\BPMN\Event\UserTaskCompletedEvent;
use KoolKode\Util\Uuid;

class CompleteUserTaskCommand extends AbstractCommand
{
	protected $taskId;
	protected $variables;
	
	public function __construct(UUID $taskId, array $variables = [])
	{
		$this->taskId = $taskId;
		$this->variables = $variables;
	}
	
	public function execute(CommandContext $context)
	{
		$task = $context->getProcessEngine()
						->getTaskService()
						->createTaskQuery()
						->taskId($this->taskId)
						->findOne();
		
		$context->notify(new UserTaskCompletedEvent($task, $context->getProcessEngine()));
		
		$sql = "	DELETE FROM `#__bpm_user_task`
					WHERE `id` = :id
		";
		$stmt = $context->prepareQuery($sql);
		$stmt->bindValue('id', $this->taskId->toBinary());
		$stmt->execute();
		
		$execution = $context->loadExecution($task->getExecutionId());

		$context->pushCommand(new SignalExecutionCommand($execution, NULL, $this->variables));
	}
}