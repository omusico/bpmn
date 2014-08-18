<?php

/*
 * This file is part of KoolKode BPMN.
*
* (c) Martin Schröder <m.schroeder2007@gmail.com>
*
* For the full copyright and license information, please view the LICENSE
* file that was distributed with this source code.
*/

namespace KoolKode\BPMN\Repository;

use KoolKode\BPMN\Engine\BinaryData;
use KoolKode\BPMN\Engine\ProcessEngine;
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
	
		return (int)$stmt->fetchNextColumn(0);
	}
	
	public function findOne()
	{
		$stmt = $this->executeSql(false, 1);
		$row = $stmt->fetchNextRow();
	
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
	
		while($row = $stmt->fetchNextRow())
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
			unserialize(BinaryData::decode($row['definition'])),
			$row['name'],
			new \DateTime('@' . $row['deployed_at'])
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
			$fields = 'd.*';
		}
	
		$sql = "	SELECT $fields
					FROM `#__process_definition` AS d
		";
	
		$alias = 1;
		$joins = [];
		$where = [];
		$params = [];
		
		if($this->latestVersion)
		{
			$joins[] = "	INNER JOIN (
								SELECT `process_key`, MAX(`revision`) AS revision
								FROM `#__process_definition`
								GROUP BY `process_key`
							) AS m USING (`process_key`, revision)
			";
		}
		
		if($this->messageEventSubscriptionName !== NULL)
		{
			$p1 = 'p' . (++$pp);
			$p2 = 'p' . (++$pp);
			
			$joins[] = 'INNER JOIN `#__process_subscription` AS s ON (s.`definition_id` = d.`id`)';
			$where[] = "s.`flags` = :$p1";
			$where[] = "s.`name` = :$p2";
			$params[$p1] = ProcessEngine::SUB_FLAG_MESSAGE;
			$params[$p2] = $this->messageEventSubscriptionName;
		}
		
		if($this->processDefinitionKey !== NULL)
		{
			$p1 = 'p' . (++$pp);
			
			$where[] = "d.`process_key` = :$p1";
			$params[$p1] = $this->processDefinitionKey;
		}
		
		if($this->processDefinitionVersion !== NULL)
		{
			$p1 = 'p' . (++$pp);
			
			$where[] = "d.`revision` = :$p1";
			$params[$p1] = $this->processDefinitionVersion;
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
