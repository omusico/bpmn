<?php

/*
 * This file is part of KoolKode BPMN.
*
* (c) Martin Schröder <m.schroeder2007@gmail.com>
*
* For the full copyright and license information, please view the LICENSE
* file that was distributed with this source code.
*/

namespace KoolKode\BPMN\Engine;

use KoolKode\BPMN\Delegate\DelegateTaskFactoryInterface;
use KoolKode\BPMN\Repository\RepositoryService;
use KoolKode\BPMN\Runtime\RuntimeService;
use KoolKode\BPMN\Task\TaskService;
use KoolKode\Database\ConnectionInterface;
use KoolKode\Database\LargeObjectStream;
use KoolKode\Database\StatementInterface;
use KoolKode\Event\EventDispatcherInterface;
use KoolKode\Expression\ExpressionContextFactoryInterface;
use KoolKode\Process\AbstractEngine;
use KoolKode\Process\Execution;
use KoolKode\Util\Uuid;

/**
 * BPMN 2.0 process engine backed by a relational database.
 * 
 * @author Martin Schröder
 */
class ProcessEngine extends AbstractEngine implements ProcessEngineInterface
{
	const SUB_FLAG_SIGNAL = 1;
	const SUB_FLAG_MESSAGE = 2;
	
	protected $executions = [];
	
	protected $conn;
	
	protected $handleTransactions;
	
	protected $delegateTaskFactory;
	
	protected $repositoryService;
	
	protected $runtimeService;
	
	protected $taskService;
	
	public function __construct(ConnectionInterface $conn, EventDispatcherInterface $dispatcher, ExpressionContextFactoryInterface $factory, $handleTransactions = true)
	{
		parent::__construct($dispatcher, $factory);
		
		$this->conn = $conn;
		$this->handleTransactions = $handleTransactions ? true : false;
			
		$this->repositoryService = new RepositoryService($this);
		$this->runtimeService = new RuntimeService($this);
		$this->taskService = new TaskService($this);
	}
	
	public function getRepositoryService()
	{
		return $this->repositoryService;
	}
	
	public function getRuntimeService()
	{
		return $this->runtimeService;
	}
	
	public function getTaskService()
	{
		return $this->taskService;
	}
	
	/**
	 * Create a prepared statement from the given SQL.
	 * 
	 * @param string $sql
	 * @return StatementInterface
	 */
	public function prepareQuery($sql)
	{
		return $this->conn->prepare($sql);
	}
	
	/**
	 * get the last ID that has been inserted / next sequence value.
	 * 
	 * @param string $seq Name of the sequence to be used.
	 * @return integer
	 */
	public function getLastInsertId($seq = NULL)
	{
		return $this->conn->lastInsertId($seq);
	}
	
	public function setDelegateTaskFactory(DelegateTaskFactoryInterface $factory = NULL)
	{
		$this->delegateTaskFactory = $factory;
	}
	
	public function createDelegateTask($typeName)
	{
		if($this->delegateTaskFactory === NULL)
		{
			throw new \RuntimeException('Process engine cannot delegate tasks without a delegate task factory');
		}
		
		return $this->delegateTaskFactory->createDelegateTask($typeName);
	}
	
	protected function performExecution(callable $callback)
	{
		$trans = false;
		
		if($this->executionDepth == 0 && $this->handleTransactions)
		{
			$this->debug('BEGIN transaction');
			
			$this->conn->beginTransaction();
			$trans = true;
		}
		
		foreach($this->executions as $info)
		{
			$this->syncExecution($info->getExecution(), $info);
		}
		
		try
		{
			$result = parent::performExecution($callback);
			
			foreach($this->executions as $info)
			{
				$this->syncExecution($info->getExecution(), $info);
			}
			
			if($trans)
			{
				$this->debug('COMMIT transaction');
				$this->conn->commit();
			}
			
			return $result;
		}
		catch(\Exception $e)
		{
			if($trans)
			{
				$this->debug('ROLL BACK transaction');
				$this->conn->rollBack();
			}
			
			throw $e;
		}
		finally
		{
			if($trans)
			{
				$this->executions = [];
			}
		}
	}
	
	public function findExecution(UUID $id)
	{
		$ref = (string)$id;
		
		if(isset($this->executions[$ref]))
		{
			return $this->executions[$ref]->getExecution();
		}
		
		$sub = ':p1';
		$params = ['p1' => $id];
		
		$sql = "	SELECT e.*, d.`definition`
					FROM `#__bpm_execution` AS e
					INNER JOIN `#__bpm_process_definition` AS d ON (d.`id` = e.`definition_id`)
					WHERE e.`process_id` IN (
						SELECT `process_id` 
						FROM `#__bpm_execution`
						WHERE `id` IN ($sub)
					)
		";
		$stmt = $this->conn->prepare($sql);
		$stmt->bindAll($params);
		$stmt->execute();
		
		$executions = [];
		$parents = [];
		$defs = [];
		
		while($row = $stmt->fetchNextRow())
		{
			$id = new UUID($row['id']);
			$pid = ($row['pid'] === NULL) ? NULL : new UUID($row['pid']);
			$processId = new UUID($row['process_id']);
			$defId = (string)new UUID($row['definition_id']);
			
			if($pid !== NULL)
			{
				$parents[(string)$id] = (string)$pid;
			}
			
			if(isset($defs[$defId]))
			{
				$definition = $defs[$defId];
			}
			else
			{
				$definition = $defs[$defId] = unserialize(BinaryData::decode($row['definition']));
			}
			
			$state = (int)$row['state'];
			$active = (float)$row['active'];
			$node = ($row['node'] === NULL) ? NULL : $definition->findNode($row['node']);
			$transition = ($row['transition'] === NULL) ? NULL : $definition->findTransition($row['transition']);
			$businessKey = $row['business_key'];
			$vars = unserialize(BinaryData::decode($row['vars']));
			
			$exec = $executions[(string)$id] = new VirtualExecution($id, $this, $definition);
			$exec->setBusinessKey($businessKey);
			$exec->setExecutionState($state);
			$exec->setNode($node);
			$exec->setTransition($transition);
			$exec->setTimestamp($active);
			$exec->setVariablesLocal($vars);
		}
		
		foreach($parents as $id => $pid)
		{
			$executions[$id]->setParentExecution($executions[$pid]);
		}
		
		foreach($executions as $execution)
		{
			$this->registerExecution($execution, $this->serializeExecution($execution));
		}
		
		return $this->executions[$ref]->getExecution();
	}
	
	public function registerExecution(Execution $execution, array $clean = NULL)
	{
		if(!$execution instanceof VirtualExecution)
		{
			throw new \InvalidArgumentException(sprintf('Execution not supported by BPMN engine: %s', get_class($execution)));
		}
		
		$info = $this->executions[(string)$execution->getId()] = new ExecutionInfo($execution, $clean);
		
		$data = $this->serializeExecution($execution);
		
		if($info->getState($data) == ExecutionInfo::STATE_NEW)
		{
			$this->syncExecution($execution, $info);
		}
	}
	
	public function serializeExecution(VirtualExecution $execution)
	{
		$parent = $execution->getParentExecution();
		$pid = ($parent === NULL) ? NULL : $parent->getId();
		$nid = ($execution->getNode() === NULL) ? NULL : $execution->getNode()->getId();
		$tid = ($execution->getTransition() === NULL) ? NULL : $execution->getTransition()->getId();
		
		return [
			'id' => $execution->getId(),
			'pid' => $pid,
			'process' => $execution->getRootExecution()->getId(),
			'def' => $execution->getProcessDefinition()->getId(),
			'state' => $execution->getState(),
			'active' => $execution->getTimestamp(),
			'node' => $nid,
			'transition' => $tid,
			'depth' => $execution->getExecutionDepth(),
			'bkey' => $execution->getBusinessKey(),
			'vars' => new BinaryData(serialize($execution->getVariablesLocal()))
		];
	}
	
	public function syncExecutionState(VirtualExecution $execution)
	{
		$id = (string)$execution->getId();
		
		if(isset($this->executions[$id]))
		{
			$this->syncExecution($execution, $this->executions[$id], false);
		}
	}
	
	protected function syncExecution(VirtualExecution $execution, ExecutionInfo $info, $syncChildExecutions = true)
	{
		$data = $this->serializeExecution($execution);
		$state = $info->getState($data);
	
		if($state == ExecutionInfo::STATE_REMOVED)
		{
			$this->debug('SYNC [delete]: {execution}', [
				'execution' => (string)$execution
			]);
			
			foreach($execution->findChildExecutions() as $child)
			{
				$this->syncExecution($child, $this->executions[(string)$child->getId()]);
			}
				
			$sql = "	DELETE FROM `#__bpm_execution`
						WHERE `id` = :id
			";
			$stmt = $this->conn->prepare($sql);
			$stmt->bindValue('id', $data['id']);
			$stmt->execute();
				
			unset($this->executions[(string)$execution->getId()]);
				
			return;
		}
	
		if($state == ExecutionInfo::STATE_MODIFIED)
		{
			$this->debug('SYNC [update]: {execution}', [
				'execution' => (string)$execution
			]);
			
			$sql = "	UPDATE `#__bpm_execution`
						SET `pid` = :pid,
							`process_id` = :process,
							`state` = :state,
							`active` = :active,
							`node` = :node,
							`depth` = :depth,
							`transition` = :transition,
							`business_key` = :bkey,
							`vars` = :vars
						WHERE `id` = :id
			";
			$stmt = $this->conn->prepare($sql);
			$stmt->bindValue('id', $data['id']);
			$stmt->bindValue('pid', $data['pid']);
			$stmt->bindValue('process', $data['process']);
			$stmt->bindValue('state', $data['state']);
			$stmt->bindValue('active', $data['active']);
			$stmt->bindValue('node', $data['node']);
			$stmt->bindValue('transition', $data['transition']);
			$stmt->bindValue('depth', $data['depth']);
			$stmt->bindValue('bkey', $data['bkey']);
			$stmt->bindValue('vars', new LargeObjectStream($data['vars']));
			$stmt->execute();
				
			$info->update($data);
		}
		elseif($state == ExecutionInfo::STATE_NEW)
		{
			$this->debug('SYNC [create]: {execution}', [
				'execution' => (string)$execution
			]);
			
			$sql = "	INSERT INTO `#__bpm_execution` (
							`id`, `pid`, `process_id`, `definition_id`, `state`, `active`,
							`node`, `transition`, `depth`, `business_key`, `vars`
						) VALUES (
							:id, :pid, :process, :def, :state, :active,
							:node, :transition, :depth, :bkey, :vars
						)
			";
			$stmt = $this->conn->prepare($sql);
			
			foreach($data as $k => $v)
			{
				if($k == 'vars')
				{
					$stmt->bindValue($k, new LargeObjectStream($v));
				}
				else
				{
					$stmt->bindValue($k, $v);					
				}
			}
			
			$stmt->execute();
				
			$info->update($data);
		}
	
		if($syncChildExecutions)
		{
			foreach($execution->findChildExecutions() as $child)
			{
				$this->syncExecution($child, $this->executions[(string)$child->getId()]);
			}
		}
	}
}
