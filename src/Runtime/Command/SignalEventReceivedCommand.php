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
use KoolKode\BPMN\Engine\BinaryData;
use KoolKode\BPMN\Engine\ProcessEngine;
use KoolKode\BPMN\Engine\VirtualExecution;
use KoolKode\BPMN\Repository\BusinessProcessDefinition;
use KoolKode\BPMN\Runtime\Command\SignalExecutionCommand;
use KoolKode\Util\UUID;

/**
 * Notifies all executions that habe subscribed to the received signal.
 * 
 * @author Martin Schröder
 */
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
		$sql = "	SELECT s.`id`, s.`execution_id`, s.`activity_id`, s.`node`
					FROM `#__event_subscription` AS s
					INNER JOIN `#__execution` AS e ON (e.`id` = s.`execution_id`)
					WHERE s.`name` = :signal
					AND s.`flags` = :flags
					ORDER BY e.`depth` DESC
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
			$stmt->bindValue('eid', $this->executionId);
		}
			
		$stmt->execute();
		
		$ids = [];
		$executions = [];
		
		foreach($stmt->fetchRows() as $row)
		{
			$execution = $executions[] = $engine->findExecution(new UUID($row['execution_id']));
			$ids[(string)$execution->getId()] = [$execution->getId(), $row['activity_id']];
			
			if($row['node'] !== NULL)
			{
				$execution->setNode($execution->getProcessDefinition()->findNode($row['node']));
				$execution->setTransition(NULL);
					
				$engine->syncExecutionState($execution);
			}
		}
		
		if(!empty($ids))
		{
			$sql = "	DELETE FROM `#__event_subscription`
						WHERE `execution_id` = :eid
						AND `activity_id` = :aid
			";
			$stmt = $engine->prepareQuery($sql);
			
			foreach($ids as $tmp)
			{
				$stmt->bindValue('eid', $tmp[0]);
				$stmt->bindValue('aid', $tmp[1]);
				$stmt->execute();
			}
		}
		
		$uuids = [];
		
		foreach($executions as $execution)
		{
			$uuids[] = $execution->getId();
			
			$engine->pushCommand(new SignalExecutionCommand($execution, $this->signal, $this->variables));
		}
		
		// Include signal start events subscriptions.
		$sql = "	SELECT s.`name` AS signal_name, d.* 
					FROM `#__process_subscription` AS s
					INNER JOIN `#__process_definition` AS d ON (d.`id` = s.`definition_id`)
					WHERE s.`flags` = :flags
					AND s.`name` = :name
		";
		$stmt = $engine->prepareQuery($sql);
		$stmt->bindValue('flags', ProcessEngine::SUB_FLAG_SIGNAL);
		$stmt->bindValue('name', $this->signal);
		$stmt->execute();
		
		while($row = $stmt->fetchNextRow())
		{
			$definition = new BusinessProcessDefinition(
				new UUID($row['id']),
				$row['process_key'],
				$row['revision'],
				unserialize(BinaryData::decode($row['definition'])),
				$row['name'],
				new \DateTime('@' . $row['deployed_at'])
			);
			
			$uuids[] = $engine->executeCommand(new StartProcessInstanceCommand(
				$definition,
				$definition->findSignalStartEvent($row['signal_name']),
				($this->sourceExecution === NULL) ? NULL : $this->sourceExecution->getBusinessKey(),
				$this->variables
			));
		}
				
		if($this->sourceExecution !== NULL)
		{
			$engine->pushCommand(new SignalExecutionCommand($this->sourceExecution));
		}
		
		return $uuids;
	}
}
