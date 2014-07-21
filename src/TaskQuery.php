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
		
		return (int)$stmt->fetchColumn(0);
	}
	
	public function findOne()
	{
		$stmt = $this->executeSql(false, 1);
		$row = $stmt->fetch(\PDO::FETCH_ASSOC);
		
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
		
		while($row = $stmt->fetch(\PDO::FETCH_ASSOC))
		{
			$result[] = $this->unserializeTask($row);
		}
		
		return $result;
	}
	
	protected function unserializeTask(array $row)
	{
		return new Task(
			new UUID($row['id']),
			new UUID($row['execution_id']),
			$row['name'],
			$row['activity'],
			new \DateTime('@' . $row['created_at']),
			empty($row['claimed_at']) ? NULL : new \DateTime('@' . $row['claimed_at']),
			$row['claimed_by']
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
			$fields = 't.*';
		}
		
		$sql = "	SELECT $fields
					FROM `#__bpm_user_task` AS t
					INNER JOIN `#__bpm_execution` AS e ON (e.`id` = t.`execution_id`)
					INNER JOIN `#__bpm_process_definition` AS d ON (d.`id` = e.`definition_id`)
		";
		
		$alias = 1;
		$joins = [];
		$where = [];
		$params = [];
		
		if($this->executionId !== NULL)
		{
			$where[] = 'e.`id` = ?';
			$params[] = $this->executionId->toBinary();
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
		
		if($this->taskDefinitionKey !== NULL)
		{
			$where[] = 't.`activity` = ?';
			$params[] = $this->taskDefinitionKey;
		}
		
		if($this->taskId !== NULL)
		{
			$where[] = 't.`id` = ?';
			$params[] = $this->taskId->toBinary();
		}
		
		if($this->taskName !== NULL)
		{
			$where[] = 't.`name` = ?';
			$params[] = $this->taskName;
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
		
		if($limit > 0)
		{
			$sql .= sprintf(' LIMIT %u OFFSET %u', $limit, $offset);
		}
		
		$stmt = $this->engine->prepareQuery($sql);
		$stmt->execute($params);
		
		return $stmt;
	}
}
