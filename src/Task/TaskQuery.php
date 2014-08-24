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
			new \DateTime('@' . $row['created_at']),
			empty($row['claimed_at']) ? NULL : new \DateTime('@' . $row['claimed_at']),
			$row['claimed_by']
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
			$where[] = 't.`claimed` IS NULL';
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
