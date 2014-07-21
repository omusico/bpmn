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
use KoolKode\BPMN\ProcessEngine;
use KoolKode\Util\Uuid;

class MessageEventReceivedCommand extends AbstractCommand
{
	protected $messageName;
	protected $executionId;
	protected $variables;
	
	public function __construct($messageName, UUID $executionId, array $variables = [])
	{
		$this->messageName = (string)$messageName;
		$this->executionId = $executionId;
		$this->variables = $variables;
	}
	
	public function getPriority()
	{
		return self::PRIORITY_DEFAULT - 100;
	}
	
	public function execute(CommandContext $context)
	{
		$sql = "	SELECT s.`id` AS sub_id, s.`execution_id` AS sub_eid, e.*, d.`definition`
					FROM `#__bpm_event_subscription` AS s
					INNER JOIN `#__bpm_execution` AS e ON (e.`id` = s.`execution_id`)
					INNER JOIN `#__bpm_process_definition` AS d ON (d.`id` = e.`definition_id`)
					WHERE s.`name` = :message
					AND s.`flags` = :flags
					AND s.`execution_id` = :eid
					ORDER BY s.`created_at`
					LIMIT 1
		";
		$stmt = $context->prepareQuery($sql);
		$stmt->bindValue('message', $this->messageName);
		$stmt->bindValue('flags', ProcessEngine::SUB_FLAG_MESSAGE);
		$stmt->bindValue('eid', $this->executionId->toBinary());
		$stmt->execute();
			
		$ids = [];
		$messageMe = [];
		
		foreach($stmt->fetchAll(\PDO::FETCH_ASSOC) as $row)
		{
			$ids[] = $row['sub_id'];
			$execution = $context->getProcessEngine()->unserializeExecution($row);
				
			if(new UUID($row['sub_eid']) == $execution->getId())
			{
				$messageMe[] = $execution;
			}
		}
		
		if(!empty($ids))
		{
			$list = implode(', ', array_fill(0, count($ids), '?'));
			$sql = "	DELETE FROM `#__bpm_event_subscription`
						WHERE `id` IN ($list)
			";
			$stmt = $context->prepareQuery($sql);
			$stmt->execute($ids);
		}
		
		foreach($messageMe as $execution)
		{
			$context->pushCommand(new SignalExecutionCommand($execution, $this->messageName, $this->variables));
		}
	}
}
