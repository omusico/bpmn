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

class ProcessDefinitionQuery
{
	protected $latestVersion;
	
	protected $messageEventSubscriptionName;
	
	protected $processDefinitionKey;
	
	protected $processDefinitionVersion;
	
	protected $engine;
	
	public function __construct(ProcessEngine $engine)
	{
		$this->engine = $engine;
	}
	
	public function latestVersion()
	{
		$this->latestVersion = true;
		
		return $this;
	}
	
	public function messageEventSubscriptionName($name)
	{
		$this->messageEventSubscriptionName = (string)$name;
		
		return $this;
	}
	
	public function processDefinitionKey($key)
	{
		$this->processDefinitionKey = (string)$key;
		
		return $this;
	}
	
	public function processDefinitionVersion($version)
	{
		$this->processDefinitionVersion = (int)$version;
		
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
			throw new \OutOfBoundsException(sprintf('No matching process definition found'));
		}
	
		return $this->unserializeProcessDefinition($row);
	}
	
	public function findAll()
	{
		$stmt = $this->executeSql();
		$result = [];
	
		while($row = $stmt->fetch(\PDO::FETCH_ASSOC))
		{
			$result[] = $this->unserializeProcessDefinition($row);
		}
	
		return $result;
	}
	
	protected function unserializeProcessDefinition(array $row)
	{
		return new BusinessProcessDefinition(
			new UUID($row['id']),
			$row['process_key'],
			$row['revision'],
			unserialize(gzuncompress($row['definition'])),
			$row['name'],
			new \DateTime('@' . $row['deployed_at'])
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
			$fields = 'd.*';
		}
	
		$sql = "	SELECT $fields
					FROM `#__bpm_process_definition` AS d
		";
	
		$alias = 1;
		$joins = [];
		$where = [];
		$params = [];
		
		if($this->latestVersion)
		{
			$joins[] = "	INNER JOIN (
								SELECT `process_key`, MAX(`revision`) AS revision
								FROM `#__bpm_process_definition`
								GROUP BY `process_key`
							) AS m USING (`process_key`, revision)
			";
		}
		
		if($this->messageEventSubscriptionName !== NULL)
		{
			$joins[] = 'INNER JOIN `#__bpm_process_subscription` AS s ON (s.`definition_id` = d.`id`)';
			$where[] = 's.`flags` = ?';
			$where[] = 's.`name` = ?';
			$params[] = ProcessEngine::SUB_FLAG_MESSAGE;
			$params[] = $this->messageEventSubscriptionName;
		}
		
		if($this->processDefinitionKey !== NULL)
		{
			$where[] = 'd.`process_key` = ?';
			$params[] = $this->processDefinitionKey;
		}
		
		if($this->processDefinitionVersion !== NULL)
		{
			 $where[] = 'd.`revision` = ?';
			 $params[] = $this->processDefinitionVersion;
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
