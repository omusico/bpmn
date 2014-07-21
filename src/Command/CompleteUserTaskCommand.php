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
		$sql = "	SELECT *
					FROM `#__bpm_user_task`
					WHERE `id` = :id
		";
		$stmt = $context->prepareQuery($sql);
		$stmt->bindValue('id', $this->taskId->toBinary());
		$stmt->execute();
		$task = $stmt->fetch(\PDO::FETCH_ASSOC);
			
		if($task === false)
		{
			throw new \OutOfBoundsException(sprintf('User Task not found: "%s"', $this->taskId));
		}
			
		$sql = "	DELETE FROM `#__bpm_user_task`
					WHERE `id` = :id
		";
		$stmt = $context->prepareQuery($sql);
		$stmt->bindValue('id', $this->taskId->toBinary());
		$stmt->execute();
		
		$execution = $context->loadExecution(new Uuid($task['execution_id']));

		$context->pushCommand(new SignalExecutionCommand($execution, NULL, $this->variables));
	}
}