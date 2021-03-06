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
use KoolKode\Database\ParamEncoderDecorator;
use KoolKode\Database\StatementInterface;
use KoolKode\Event\EventDispatcherInterface;
use KoolKode\Expression\ExpressionContextFactoryInterface;
use KoolKode\Process\AbstractEngine;
use KoolKode\Process\Execution;
use KoolKode\Util\UnicodeString;
use KoolKode\Util\UUID;

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
	
	protected $interceptors = [];
	
	protected $conn;
	
	protected $handleTransactions;
	
	protected $delegateTaskFactory;
	
	protected $repositoryService;
	
	protected $runtimeService;
	
	protected $taskService;
	
	public function __construct(ConnectionInterface $conn, EventDispatcherInterface $dispatcher, ExpressionContextFactoryInterface $factory, $handleTransactions = true)
	{
		parent::__construct($dispatcher, $factory);
		
		$conn = new ParamEncoderDecorator($conn);
		$conn->registerParamEncoder(new BinaryDataParamEncoder());
		$conn->registerParamEncoder(new IdentifierParamEncoder());
		
		$this->conn = $conn;
		$this->handleTransactions = $handleTransactions ? true : false;
		
		$this->repositoryService = new RepositoryService($this);
		$this->runtimeService = new RuntimeService($this);
		$this->taskService = new TaskService($this);
	}
	
	public function __debugInfo()
	{
		return [
			'conn' => $this->conn,
			'transactional' => $this->handleTransactions,
			'executionDepth' => $this->executionDepth,
			'executionCount' => $this->executionCount,
			'executions' => array_values(array_map(function(ExecutionInfo $info) {
				return $info->getExecution();
			}, $this->executions))
		];
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
	
	public function registerExecutionInterceptor(ExecutionInterceptorInterface $interceptor)
	{
		return $this->interceptors[] = $interceptor;
	}
	
	public function unregisterExecutionInterceptor(ExecutionInterceptorInterface $interceptor)
	{
		if(false !== ($index = array_search($interceptor, $this->interceptors, true)))
		{
			unset($this->interceptors[$index]);
		}
		
		return $interceptor;
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
			$chain = new ExecutionInterceptorChain(function() use($callback) {
				return parent::performExecution($callback);
			}, $this->executionDepth, $this->interceptors);
			
			$result = $chain->performExecution($this->executionDepth);
			
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
					FROM `#__execution` AS e
					INNER JOIN `#__process_definition` AS d ON (d.`id` = e.`definition_id`)
					WHERE e.`process_id` IN (
						SELECT `process_id` 
						FROM `#__execution`
						WHERE `id` IN ($sub)
					)
		";
		$stmt = $this->conn->prepare($sql);
		$stmt->bindAll($params);
		$stmt->execute();
		
		$variables = [];
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
			
			$variables[(string)$id] = [];
			
			$exec = $executions[(string)$id] = new VirtualExecution($id, $this, $definition);
			$exec->setBusinessKey($businessKey);
			$exec->setExecutionState($state);
			$exec->setNode($node);
			$exec->setTransition($transition);
			$exec->setTimestamp($active);
		}
		
		foreach($parents as $id => $pid)
		{
			$executions[$id]->setParentExecution($executions[$pid]);
		}
		
		if(!empty($variables))
		{
			$params = [];
			
			foreach(array_keys($variables) as $i => $k)
			{
				$params['p' . $i] = new UUID($k);
			}
			
			$placeholders = implode(', ', array_map(function($p) {
				return ':' . $p;
			}, array_keys($params)));
			
			$sql = "	SELECT `execution_id`, `name`, `value_blob`
						FROM `#__execution_variables`
						WHERE `execution_id` IN ($placeholders)
			";
			$stmt = $this->conn->prepare($sql);
			$stmt->bindAll($params);
			$stmt->execute();
			
			while(false !== ($row = $stmt->fetchNextRow()))
			{
				$variables[(string)new UUID($row['execution_id'])][$row['name']] = unserialize(BinaryData::decode($row['value_blob']));
			}
		}
		
		foreach($variables as $id => $vars)
		{
			$executions[$id]->setVariablesLocal($vars);
		}
		
		foreach($executions as $execution)
		{
			$this->registerExecution($execution, $this->serializeExecution($execution));
		}
		
		if(empty($this->executions[$ref]))
		{
			throw new \OutOfBoundsException(sprintf('Execution not found: "%s"', $ref));
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
			'def' => $execution->getProcessModel()->getId(),
			'state' => $execution->getState(),
			'active' => $execution->getTimestamp(),
			'node' => $nid,
			'transition' => $tid,
			'depth' => $execution->getExecutionDepth(),
			'bkey' => $execution->getBusinessKey(),
			'vars' => $execution->getVariablesLocal()
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
				
			$sql = "	DELETE FROM `#__execution`
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
			
			$sql = "	UPDATE `#__execution`
						SET `pid` = :pid,
							`process_id` = :process,
							`state` = :state,
							`active` = :active,
							`node` = :node,
							`depth` = :depth,
							`transition` = :transition,
							`business_key` = :bkey
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
			$stmt->execute();
			
			$delta = $info->getVariableDelta($data['vars']);
			
			$info->update($data);
		}
		elseif($state == ExecutionInfo::STATE_NEW)
		{
			$this->debug('SYNC [create]: {execution}', [
				'execution' => (string)$execution
			]);
			
			$sql = "	INSERT INTO `#__execution` (
							`id`, `pid`, `process_id`, `definition_id`, `state`, `active`,
							`node`, `transition`, `depth`, `business_key`
						) VALUES (
							:id, :pid, :process, :def, :state, :active,
							:node, :transition, :depth, :bkey
						)
			";
			$stmt = $this->conn->prepare($sql);
			
			foreach($data as $k => $v)
			{
				if($k == 'vars')
				{
					continue;
				}
				
				$stmt->bindValue($k, $v);
			}
			
			$stmt->execute();

			$delta = $info->getVariableDelta($data['vars']);
			
			$info->update($data);
		}
		
		if(!empty($delta))
		{
			if(!empty($delta[ExecutionInfo::STATE_REMOVED]))
			{
				$params = [];
				
				foreach($delta[ExecutionInfo::STATE_REMOVED] as $k)
				{
					$params['n' . count($params)] = $k;
				}
				
				$placeholders = implode(', ', array_map(function($p) {
					return ':' . $p;
				}, array_keys($params)));
				
				$sql = "	DELETE FROM `#__execution_variables`
							WHERE `execution_id` = :eid
							AND `name` IN ($placeholders)
				";
				$stmt = $this->conn->prepare($sql);
				$stmt->bindValue('eid', $data['id']);
				$stmt->bindAll($params);
				$stmt->execute();
			}
			
			if(!empty($delta[ExecutionInfo::STATE_NEW]))
			{
				$sql = "	INSERT INTO `#__execution_variables`
								(`execution_id`, `name`, `value`, `value_blob`)
							VALUES
								(:eid, :name, :value, :blob)
				";
				$stmt = $this->conn->prepare($sql);
				$stmt->bindValue('eid', $data['id']);
				
				foreach($delta[ExecutionInfo::STATE_NEW] as $k)
				{
					$value = NULL;
					
					if(is_scalar($data['vars'][$k]))
					{
						$val = $data['vars'][$k];
						
						if(is_bool($val))
						{
							$val = $val ? '1' : '0';
						}
						
						$value = new UnicodeString(trim($val));
						
						if($value->length() > 250)
						{
							$value = $value->substring(0, 250);
						}
						
						$value = $value->toLowerCase();
					}
					
					$stmt->bindValue('name', $k);
					$stmt->bindValue('value', $value);
					$stmt->bindValue('blob', new BinaryData(serialize($data['vars'][$k])));
					$stmt->execute();
				}
			}
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
