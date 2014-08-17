<?php

/*
 * This file is part of KoolKode BPMN.
*
* (c) Martin Schröder <m.schroeder2007@gmail.com>
*
* For the full copyright and license information, please view the LICENSE
* file that was distributed with this source code.
*/

namespace KoolKode\BPMN\Runtime;

use KoolKode\BPMN\Engine\BinaryData;
use KoolKode\BPMN\Engine\ProcessEngine;
use KoolKode\BPMN\Repository\BusinessProcessDefinition;
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
	
	protected $queryProcess;
	protected $engine;
	
	public function __construct(ProcessEngine $engine, $queryProcess = false)
	{
		$this->engine = $engine;
		$this->queryProcess = $queryProcess ? true : false;
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
		
		return (int)$stmt->fetchNextColumn(0);
	}
	
	public function findOne()
	{
		$stmt = $this->executeSql(false, 1);
		$row = $stmt->fetchNextRow();
		
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
		
		while($row = $stmt->fetchNextRow())
		{
			$result[] = $this->unserializeExecution($row);
		}
		
		return $result;
	}
	
	protected function unserializeExecution(array $row)
	{
		$def = new BusinessProcessDefinition(
			new UUID($row['def_id']),
			$row['def_key'],
			$row['def_rev'],
			unserialize(BinaryData::decode($row['def_data'])),
			$row['def_name'],
			new \DateTime('@' . $row['def_deployed'])
		);
		
		return new Execution(
			$def,
			new UUID($row['id']),
			new UUID($row['process_id']),
			empty($row['pid']) ? NULL : new UUID($row['pid']),
			$row['node'],
			(int)$row['state'] & \KoolKode\Process\Execution::STATE_TERMINATE,
			$row['business_key']
		);
	}
	
	protected function executeSql($count = false, $limit = 0, $offset = 0)
	{
		$pp = 0;
		
		if($count)
		{
			$fields = 'COUNT(*) AS num';
		}
		else
		{
			$fields = '	e.*,
						d.`id` AS def_id,
						d.`process_key` AS def_key,
						d.`revision` AS def_rev,
						d.`definition` AS def_data,
						d.`name` AS def_name,
						d.`deployed_at` AS def_deployed
			';
		}
		
		$sql = "	SELECT $fields
					FROM `#__bpm_execution` AS e
					INNER JOIN `#__bpm_process_definition` AS d ON (d.`id` = e.`definition_id`)
		";
		
		$alias = 1;
		$joins = [];
		$where = [];
		$params = [];
		
		if($this->queryProcess)
		{
			$where[] = 'e.`id` = e.`process_id`';
		}
		
		if($this->activityId !== NULL)
		{
			$p1 = 'p' . (++$pp);
			
			$where[] = "e.`node` = :$p1";
			$params[$p1] = $this->activityId;
		}
		
		if($this->executionId !== NULL)
		{
			$p1 = 'p' . (++$pp);
			
			$where[] = "e.`id` = :$p1";
			$params[$p1] = $this->executionId;
		}
		
		if($this->processBusinessKey != NULL)
		{
			$p1 = 'p' . (++$pp);
			
			$where[] = "e.`business_key` = :$p1";
			$params[$p1] = $this->processBusinessKey;
		}
		
		if($this->processDefinitionKey !== NULL)
		{
			$p1 = 'p' . (++$pp);
			
			$where[] = "d.`process_key` = :$p1";
			$params[$p1] = $this->processDefinitionKey;
		}
		
		if($this->processInstanceId !== NULL)
		{
			$p1 = 'p' . (++$pp);
			
			$where[] = "e.`process_id` = :$p1";
			$params[$p1] = $this->processInstanceId;
		}
		
		foreach($this->signalEventSubscriptionNames as $name)
		{
			$joins[] = 'INNER JOIN `#__bpm_event_subscription` AS s' . $alias . " ON (s$alias.`execution_id` = e.`id`)";
			
			$p1 = 'p' . (++$pp);
			$p2 = 'p' . (++$pp);
			
			$where[] = "s$alias.`flags` = :$p1";
			$params[$p1] = ProcessEngine::SUB_FLAG_SIGNAL;
			
			$where[] = "s$alias.`name` = :$p2";
			$params[$p2] = $name;
			
			$alias++;
		}
		
		foreach($this->messageEventSubscriptionNames as $name)
		{
			$joins[] = 'INNER JOIN `#__bpm_event_subscription` AS s' . $alias . " ON (s$alias.`execution_id` = e.`id`)";
			
			$p1 = 'p' . (++$pp);
			$p2 = 'p' . (++$pp);
			
			$where[] = "s$alias.`flags` = :$p1";
			$params[$p1] = ProcessEngine::SUB_FLAG_MESSAGE;
				
			$where[] = "s$alias.`name` = :$p2";
			$params[$p2] = $name;
				
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
		
		$stmt = $this->engine->prepareQuery($sql);
		$stmt->bindAll($params);
		$stmt->setLimit($limit);
		$stmt->setOffset($offset);
		$stmt->execute();
		
		return $stmt;
	}
}
