<?php

/*
 * This file is part of KoolKode BPMN.
*
* (c) Martin Schröder <m.schroeder2007@gmail.com>
*
* For the full copyright and license information, please view the LICENSE
* file that was distributed with this source code.
*/

namespace KoolKode\BPMN\Task;

use KoolKode\BPMN\Engine\AbstractQuery;
use KoolKode\BPMN\Engine\ProcessEngine;
use KoolKode\Util\UUID;

/**
 * Query for active user tasks.
 * 
 * @author Martin Schröder
 */
class TaskQuery extends AbstractQuery
{
	protected $executionId;
	protected $processInstanceId;
	protected $processDefinitionKey;
	protected $processBusinessKey;
	
	protected $taskDefinitionKey;
	protected $taskId;
	protected $taskName;
	
	protected $taskUnassigned;
	protected $taskAssignee;
	
	protected $dueBefore;
	protected $dueAfter;
	protected $taskCreatedBefore;
	protected $taskCreatedAfter;
	
	protected $taskPriority;
	protected $taskMinPriority;
	protected $taskMaxPriority;
	
	protected $engine;
	
	public function __construct(ProcessEngine $engine)
	{
		$this->engine = $engine;
	}
	
	public function executionId($id)
	{
		$this->populateMultiProperty($this->executionId, $id, function($value) {
			return new UUID($value);
		});
		
		return $this;
	}
	
	public function processInstanceId($id)
	{
		$this->populateMultiProperty($this->processInstanceId, $id, function($value) {
			return new UUID($value);
		});
		
		return $this;
	}
	
	public function processDefinitionKey($key)
	{
		$this->populateMultiProperty($this->processDefinitionKey, $key);
		
		return $this;
	}
	
	public function processBusinessKey($key)
	{
		$this->populateMultiProperty($this->processBusinessKey, $key, function($value) {
			return new UUID($value);
		});
		
		return $this;
	}
	
	public function taskDefinitionKey($key)
	{
		$this->populateMultiProperty($this->taskDefinitionKey, $key);
		
		return $this;
	}
	
	public function taskId($id)
	{
		$this->populateMultiProperty($this->taskId, $id, function($value) {
			return new UUID($value);
		});
		
		return $this;
	}
	
	public function taskName($name)
	{
		$this->populateMultiProperty($this->taskName, $name);
		
		return $this;
	}
	
	public function taskUnassigned()
	{
		$this->taskUnassigned = true;
		
		return $this;
	}
	
	public function taskAssignee($assignee)
	{
		$this->populateMultiProperty($this->taskAssignee, $assignee);
		
		return $this;
	}
	
	public function dueBefore(\DateTimeInterface $date)
	{
		$this->dueBefore = $date->getTimestamp();
		
		return $this;
	}
	
	public function dueAfter(\DateTimeInterface $date)
	{
		$this->dueAfter = $date->getTimestamp();
		
		return $this;
	}
	
	public function taskPriority($priority)
	{
		$this->populateMultiProperty($this->taskPriority, $priority, function($value) {
			return (int)$value;
		});
		
		return $this;
	}
	
	public function taskMinPriority($priority)
	{
		$this->taskMinPriority = (int)$priority;
		
		return $this;
	}
	
	public function taskMaxPriority($priority)
	{
		$this->taskMaxPriority = (int)$priority;
	
		return $this;
	}
	
	public function taskCreatedBefore(\DateTimeInterface $date)
	{
		$this->taskCreatedBefore = $date->getTimestamp();
	
		return $this;
	}
	
	public function taskCreatedAfter(\DateTimeInterface $date)
	{
		$this->taskCreatedAfter = $date->getTimestamp();
	
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
			throw new \OutOfBoundsException(sprintf('No matching task found'));
		}
		
		return $this->unserializeTask($row);
	}
	
	public function findAll()
	{
		$stmt = $this->executeSql();
		$result = [];
		
		while($row = $stmt->fetchNextRow())
		{
			$result[] = $this->unserializeTask($row);
		}
		
		return $result;
	}
	
	protected function unserializeTask(array $row)
	{
		$task = new Task(
			new UUID($row['id']),
			new UUID($row['execution_id']),
			$row['name'],
			$row['activity'],
			new \DateTimeImmutable('@' . $row['created_at']),
			empty($row['claimed_at']) ? NULL : new \DateTimeImmutable('@' . $row['claimed_at']),
			$row['claimed_by'],
			$row['priority'],
			empty($row['due_at']) ? NULL : new \DateTimeImmutable('@' . $row['due_at'])
		);
		$task->setDocumentation($row['documentation']);
		
		return $task;
	}
	
	protected function executeSql($count = false, $limit = 0, $offset = 0)
	{
		if($count)
		{
			$fields = 'COUNT(*) AS num';
		}
		else
		{
			$fields = 't.*';
		}
		
		$sql = "	SELECT $fields
					FROM `#__user_task` AS t
					INNER JOIN `#__execution` AS e ON (e.`id` = t.`execution_id`)
					INNER JOIN `#__process_definition` AS d ON (d.`id` = e.`definition_id`)
		";
		
		$alias = 1;
		$joins = [];
		$where = [];
		$params = [];
		
		$this->buildPredicate("e.`id`", $this->executionId, $where, $params);
		$this->buildPredicate("e.`process_id`", $this->processInstanceId, $where, $params);
		$this->buildPredicate("e.`business_key`", $this->processBusinessKey, $where, $params);
		$this->buildPredicate("d.`process_key`", $this->processDefinitionKey, $where, $params);
		
		$this->buildPredicate("t.`id`", $this->taskId, $where, $params);
		$this->buildPredicate("t.`activity`", $this->taskDefinitionKey, $where, $params);
		$this->buildPredicate("t.`name`", $this->taskName, $where, $params);
		
		$this->buildPredicate("t.`claimed_by`", $this->taskAssignee, $where, $params);
		
		if($this->taskUnassigned)
		{
			$where[] = 't.`claimed_by` IS NULL';
		}
		
		if($this->dueAfter !== NULL || $this->dueBefore !== NULL)
		{
			$where[] = "t.`due_at` IS NOT NULL";
		}
		
		if($this->dueBefore !== NULL)
		{
			$p1 = 'p' . count($params);
			
			$where[] = "t.`due_at` < :$p1";
			$params[$p1] = $this->dueBefore;
		}
		
		if($this->dueAfter !== NULL)
		{
			$p1 = 'p' . count($params);
		
			$where[] = "t.`due_at` > :$p1";
			$params[$p1] = $this->dueAfter;
		}
		
		if($this->taskCreatedBefore !== NULL)
		{
			$p1 = 'p' . count($params);
		
			$where[] = "t.`created_at` < :$p1";
			$params[$p1] = $this->taskCreatedBefore;
		}
		
		if($this->taskCreatedAfter !== NULL)
		{
			$p1 = 'p' . count($params);
		
			$where[] = "t.`created_at` > :$p1";
			$params[$p1] = $this->taskCreatedAfter;
		}
		
		$this->buildPredicate("t.`priority`", $this->taskPriority, $where, $params);
		
		if($this->taskMinPriority !== NULL)
		{
			$p1 = 'p' . count($params);
			
			$where[] = "t.`priority` >= :$p1";
			$params[$p1] = $this->taskMinPriority;
		}
		
		if($this->taskMaxPriority !== NULL)
		{
			$p1 = 'p' . count($params);
				
			$where[] = "t.`priority` <= :$p1";
			$params[$p1] = $this->taskMaxPriority;
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
