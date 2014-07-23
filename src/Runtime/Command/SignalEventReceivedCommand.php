<?php

/*
 * This file is part of KoolKode BPMN.
*
* (c) Martin Schröder <m.schroeder2007@gmail.com>
*
* For the full copyright and license information, please view the LICENSE
* file that was distributed with this source code.
*/

namespace KoolKode\BPMN\Runtime\Command;

use KoolKode\BPMN\Engine\AbstractBusinessCommand;
use KoolKode\BPMN\Engine\ProcessEngine;
use KoolKode\BPMN\Runtime\Command\SignalExecutionCommand;
use KoolKode\Util\Uuid;

class SignalEventReceivedCommand extends AbstractBusinessCommand
{
	protected $signal;
	
	protected $variables;
	
	protected $executionId;
	
	public function __construct($signal, UUID $executionId = NULL, array $variables = [])
	{
		$this->signal = (string)$signal;
		$this->variables = $variables;
		$this->executionId = $executionId;
	}
	
	public function getPriority()
	{
		return self::PRIORITY_DEFAULT - 100;
	}
	
	public function executeCommand(ProcessEngine $engine)
	{
		$sql = "	SELECT  s.`id` AS sub_id, s.`execution_id` AS sub_eid,
							e.*, d.`definition`
					FROM `#__bpm_event_subscription` AS s
					INNER JOIN `#__bpm_execution` AS e ON (e.`process_id` = s.`process_instance_id`)
					INNER JOIN `#__bpm_process_definition` AS d ON (d.`id` = e.`definition_id`)
					WHERE s.`name` = :signal
					AND s.`flags` = :flags
		";
			
		if($this->executionId !== NULL)
		{
			$sql .= ' AND s.`execution_id` = :eid';
		}
			
		$stmt = $engine->prepareQuery($sql);
		$stmt->bindValue('signal', $this->signal);
		$stmt->bindValue('flags', ProcessEngine::SUB_FLAG_SIGNAL);
			
		if($this->executionId !== NULL)
		{
			$stmt->bindValue('eid', $this->executionId->toBinary());
		}
			
		$stmt->execute();
			
		$ids = [];
		$signalMe = [];
			
		foreach($stmt->fetchAll(\PDO::FETCH_ASSOC) as $row)
		{
			$ids[] = $row['sub_id'];
			$execution = $engine->unserializeExecution($row);
		
			if(new UUID($row['sub_eid']) == $execution->getId())
			{
				$signalMe[] = $execution;
			}
		}
			
		if(!empty($ids))
		{
			$list = implode(', ', array_fill(0, count($ids), '?'));
			$sql = "	DELETE FROM `#__bpm_event_subscription`
						WHERE `id` IN ($list)
			";
			$stmt = $engine->prepareQuery($sql);
			$stmt->execute($ids);
		}
			
		foreach($signalMe as $execution)
		{
			$engine->pushCommand(new SignalExecutionCommand($execution, $this->signal, $this->variables));
		}
	}
}
