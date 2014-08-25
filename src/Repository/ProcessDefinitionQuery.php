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

use KoolKode\BPMN\Engine\AbstractQuery;
use KoolKode\BPMN\Engine\BinaryData;
use KoolKode\BPMN\Engine\ProcessEngine;
use KoolKode\Util\UUID;

/**
 * Query for deployed process definitions.
 * 
 * @author Martin Schröder
 */
class ProcessDefinitionQuery extends AbstractQuery
{
	protected $processDefinitionId;
	
	protected $processDefinitionKey;
	
	protected $processDefinitionVersion;
	
	protected $deploymentId;
	
	protected $latestVersion;
	
	protected $messageEventSubscriptionNames;
	
	protected $signalEventSubscriptionNames;
	
	protected $engine;
	
	public function __construct(ProcessEngine $engine)
	{
		$this->engine = $engine;
	}
	
	public function processDefinitionId($processDefinitionId)
	{
		$this->populateMultiProperty($this->processDefinitionId, $processDefinitionId, function($value) {
			return new UUID($value);
		});
		
		return $this;
	}
	
	public function processDefinitionKey($key)
	{
		$this->populateMultiProperty($this->processDefinitionKey, $key);
	
		return $this;
	}
	
	public function processDefinitionVersion($version)
	{
		$this->populateMultiProperty($this->processDefinitionVersion, $version, function($value) {
			return (int)$value;
		});
	
		return $this;
	}
	
	public function deploymentId($id)
	{
		$this->populateMultiProperty($this->deploymentId, $id, function($value) {
			return new UUID($value);
		});
		
		return $this;
	}
	
	public function latestVersion()
	{
		$this->latestVersion = true;
		
		return $this;
	}
	
	public function messageEventSubscriptionName($name)
	{
		$this->messageEventSubscriptionNames[] = [];
		$this->populateMultiProperty($this->messageEventSubscriptionNames[count($this->messageEventSubscriptionNames) - 1], $name);
		
		return $this;
	}
	
	public function signalEventSubscriptionName($name)
	{
		$this->signalEventSubscriptionNames[] = [];
		$this->populateMultiProperty($this->signalEventSubscriptionNames[count($this->signalEventSubscriptionNames) - 1], $name);
		
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
			new \DateTimeImmutable('@' . $row['deployed_at']),
			empty($row['deployment_id']) ? NULL : new UUID($row['deployment_id'])
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
			$fields = 'p.*';
		}
	
		$sql = "	SELECT $fields
					FROM `#__process_definition` AS p
					LEFT JOIN `#__deployment` AS d ON (d.`id` = p.`deployment_id`)
		";
	
		$alias = 1;
		$joins = [];
		$where = [];
		$params = [];
		
		$this->buildPredicate("p.`id`", $this->processDefinitionId, $where, $params);
		$this->buildPredicate("p.`process_key`", $this->processDefinitionKey, $where, $params);
		$this->buildPredicate("p.`revision`", $this->processDefinitionVersion, $where, $params);
		$this->buildPredicate("d.`id`", $this->deploymentId, $where, $params);
		
		foreach((array)$this->messageEventSubscriptionNames as $name)
		{
			$joins[] = "INNER JOIN `#__process_subscription` AS s$alias ON (s$alias.`definition_id` = p.`id`)";
			
			$p1 = 'p' . count($params);
			
			$where[] = "s$alias.`flags` = :$p1";
			$params[$p1] = ProcessEngine::SUB_FLAG_MESSAGE;
			
			$this->buildPredicate("s$alias.`name`", $name, $where, $params);
			
			$alias++;
		}
		
		foreach((array)$this->signalEventSubscriptionNames as $name)
		{
			$joins[] = "INNER JOIN `#__process_subscription` AS s$alias ON (s$alias.`definition_id` = p.`id`)";
				
			$p1 = 'p' . count($params);
				
			$where[] = "s$alias.`flags` = :$p1";
			$params[$p1] = ProcessEngine::SUB_FLAG_SIGNAL;
				
			$this->buildPredicate("s$alias.`name`", $name, $where, $params);
				
			$alias++;
		}
		
		if($this->latestVersion)
		{
			// Using an anti-join to improve query performance (no need for aggregate functions).
			$joins[] = "	LEFT JOIN `#__process_definition` AS p2 ON (
								p2.`process_key` = p.`process_key`
								AND p2.`revision` > p.`revision`
							)
			";
			$where[] = "p2.`revision` IS NULL";
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
