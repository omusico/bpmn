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
use KoolKode\BPMN\Engine\VirtualExecution;
use KoolKode\BPMN\Runtime\Command\SignalExecutionCommand;
use KoolKode\Util\UUID;

class SignalEventReceivedCommand extends AbstractBusinessCommand
{
	protected $signal;
	
	protected $variables;
	
	protected $executionId;
	
	protected $sourceExecution;
	
	public function __construct($signal, UUID $executionId = NULL, array $variables = [], VirtualExecution $sourceExecution = NULL)
	{
		$this->signal = (string)$signal;
		$this->variables = $variables;
		$this->executionId = $executionId;
		$this->sourceExecution = $sourceExecution;
	}
	
	public function getPriority()
	{
		return self::PRIORITY_DEFAULT - 100;
	}
	
	public function executeCommand(ProcessEngine $engine)
	{
		$sql = "	SELECT s.`id`, s.`execution_id`, s.`node`
					FROM `#__bpm_event_subscription` AS s
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
		$executions = [];
		
		foreach($stmt->fetchAll(\PDO::FETCH_ASSOC) as $row)
		{
			$execution = $executions[] = $engine->findExecution(new UUID($row['execution_id']));
			$ids[(string)$execution->getId()] = $execution->getId()->toBinary();
			
			if($row['node'] !== NULL)
			{
				$execution->setNode($execution->getProcessDefinition()->findNode($row['node']));
				$execution->setTransition(NULL);
					
				$engine->syncExecutionState($execution);
			}
		}
		
		if(!empty($ids))
		{
			$list = implode(', ', array_fill(0, count($ids), '?'));
			$sql = "	DELETE FROM `#__bpm_event_subscription`
						WHERE `id` IN ($list)
			";
			$stmt = $engine->prepareQuery($sql);
			$stmt->execute(array_values($ids));
		}
		
		foreach($executions as $execution)
		{
			$engine->pushCommand(new SignalExecutionCommand($execution, $this->signal, $this->variables));
		}
		
		if($this->sourceExecution !== NULL)
		{
			$engine->pushCommand(new SignalExecutionCommand($this->sourceExecution));
		}
	}
}
