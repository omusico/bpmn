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

use KoolKode\BPMN\Engine\ProcessEngine;
use KoolKode\Util\UUID;

class TaskQuery
{
	protected $executionId;
	protected $processDefinitionKey;
	protected $processInstanceId;
	protected $taskDefinitionKey;
	protected $taskId;
	protected $taskName;
	protected $taskUnassigned;
	
	protected $engine;
	
	public function __construct(ProcessEngine $engine)
	{
		$this->engine = $engine;
	}
	
	public function executionId($id)
	{
		$this->executionId = new UUID($id);
		
		return $this;
	}
	
	public function processDefinitionKey($key)
	{
		$this->processDefinitionKey = (string)$key;
		
		return $this;
	}
	
	public function processInstanceId($id)
	{
		$this->processInstanceId = new UUID($id);
		
		return $this;
	}
	
	public function taskDefinitionKey($key)
	{
		$this->taskDefinitionKey = (string)$key;
		
		return $this;
	}
	
	public function taskId($id)
	{
		$this->taskId = new UUID($id);
		
		return $this;
	}
	
	public function taskName($name)
	{
		$this->taskName = (string)$name;
		
		return $this;
	}
	
	public function taskUnassigned()
	{
		$this->taskUnassigned = true;
		
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
		$pp = 0;
		
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
		
		if($this->executionId !== NULL)
		{
			$p1 = 'p' . (++$pp);
			
			$where[] = "e.`id` = :$p1";
			$params[$p1] = $this->executionId;
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
		
		if($this->taskDefinitionKey !== NULL)
		{
			$p1 = 'p' . (++$pp);
			
			$where[] = "t.`activity` = :$p1";
			$params[$p1] = $this->taskDefinitionKey;
		}
		
		if($this->taskId !== NULL)
		{
			$p1 = 'p' . (++$pp);
			
			$where[] = "t.`id` = :$p1";
			$params[$p1] = $this->taskId;
		}
		
		if($this->taskName !== NULL)
		{
			$p1 = 'p' . (++$pp);
			
			$where[] = "t.`name` = :$p1";
			$params[$p1] = $this->taskName;
		}
		
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
