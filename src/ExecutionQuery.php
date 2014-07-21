<?php

/*
 * This file is part of KoolKode BPMN.
*
* (c) Martin Schröder <m.schroeder2007@gmail.com>
*
* For the full copyright and license information, please view the LICENSE
* file that was distributed with this source code.
*/

namespace KoolKode\BPMN;

use KoolKode\Util\UUID;

class ExecutionQuery
{
	protected $processInstanceId;
	protected $executionId;
	protected $activityId;
	protected $processBusinessKey;
	protected $processDefinitionKey;
	
	protected $signalEventSubscriptionNames = [];
	protected $messageEventSubscriptionNames = [];
	
	protected $engine;
	
	public function __construct(ProcessEngine $engine)
	{
		$this->engine = $engine;
	}
	
	public function processInstanceId($id)
	{
		$this->processInstanceId = new UUID($id);
		
		return $this;
	}
	
	public function executionId($id)
	{
		$this->executionId = new UUID($id);
		
		return $this;
	}
	
	public function activityId($id)
	{
		$this->activityId = (string)$id;
		
		return $this;
	}
	
	public function processBusinessKey($key)
	{
		$this->processBusinessKey = (string)$key;
		
		return $this;
	}
	
	public function processDefinitionKey($key)
	{
		$this->processDefinitionKey = (string)$key;
		
		return $this;
	}
	
	public function signalEventSubscriptionName($signalName)
	{
		$this->signalEventSubscriptionNames[] = (string)$signalName;
		
		return $this;
	}
	
	public function messageEventSubscriptionName($messageName)
	{
		$this->messageEventSubscriptionNames[] = (string)$messageName;
		
		return $this;
	}
	
	public function count()
	{
		$stmt = $this->executeSql(true);
		
		return (int)$stmt->fetchColumn(0);
	}
	
	public function findOne()
	{
		$stmt = $this->executeSql(false, 1);
		$row = $stmt->fetch(\PDO::FETCH_ASSOC);
		
		if($row === false)
		{
			throw new \OutOfBoundsException(sprintf('No matching execution found'));
		}
		
		return $this->unserializeExecution($row);
	}
	
	public function findAll()
	{
		$stmt = $this->executeSql();
		$result = [];
		
		while($row = $stmt->fetch(\PDO::FETCH_ASSOC))
		{
			$result[] = $this->unserializeExecution($row);
		}
		
		return $result;
	}
	
	protected function unserializeExecution(array $row)
	{
		return new Execution(
			new UUID($row['id']),
			new UUID($row['process_id']),
			empty($row['pid']) ? NULL : new UUID($row['pid']),
			$row['node'],
			(int)$row['state'] & \KoolKode\Process\Execution::STATE_TERMINATE
		);
	}
	
	protected function executeSql($count = false, $limit = 0, $offset = 0)
	{
		if($count)
		{
			$fields = 'COUNT(*) AS num';
		}
		else
		{
			$fields = 'e.*, d.`definition`';
		}
		
		$sql = "	SELECT $fields
					FROM `#__bpm_execution` AS e
					INNER JOIN `#__bpm_process_definition` AS d ON (d.`id` = e.`definition_id`)
		";
		
		$alias = 1;
		$joins = [];
		$where = [];
		$params = [];
		
		if($this->activityId !== NULL)
		{
			$where[] = 'e.`node` = ?';
			$params[] = $this->activityId;
		}
		
		if($this->executionId !== NULL)
		{
			$where[] = 'e.`id` = ?';
			$params[] = $this->executionId->toBinary();
		}
		
		if($this->processBusinessKey != NULL)
		{
			$where[] = 'e.`business_key` = ?';
			$params[] = $this->processBusinessKey;
		}
		
		if($this->processDefinitionKey !== NULL)
		{
			$where[] = 'd.`process_key` = ?';
			$params[] = $this->processDefinitionKey;
		}
		
		if($this->processInstanceId !== NULL)
		{
			$where[] = 'e.`process_id` = ?';
			$params[] = $this->processInstanceId->toBinary();
		}
		
		foreach($this->signalEventSubscriptionNames as $name)
		{
			$joins[] = 'INNER JOIN `#__bpm_event_subscription` AS s' . $alias . " ON (s$alias.`execution_id` = e.`id`)";
			
			$where[] = "s$alias.`flags` = ?";
			$params[] = ProcessEngine::SUB_FLAG_SIGNAL;
			
			$where[] = "s$alias.`name` = ?";
			$params[] = $name;
			
			$alias++;
		}
		
		foreach($this->messageEventSubscriptionNames as $name)
		{
			$joins[] = 'INNER JOIN `#__bpm_event_subscription` AS s' . $alias . " ON (s$alias.`execution_id` = e.`id`)";
				
			$where[] = "s$alias.`flags` = ?";
			$params[] = ProcessEngine::SUB_FLAG_MESSAGE;
				
			$where[] = "s$alias.`name` = ?";
			$params[] = $name;
				
			$alias++;
		}
		
		foreach($joins as $join)
		{
			$sql .= ' ' . $join;
		}
		
		if(!empty($where))
		{
			$sql .= ' WHERE ' . implode(' AND ', $where);
		}
		
		if($limit > 0)
		{
			$sql .= sprintf(' LIMIT %u OFFSET %u', $limit, $offset);
		}
		
		$stmt = $this->engine->prepareQuery($sql);
		$stmt->execute($params);
		
		return $stmt;
	}
}
