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

use KoolKode\Process\EngineInterface;
use KoolKode\Process\ProcessDefinition;
use KoolKode\Util\Uuid;

class ProcessEngine
{
	const SUB_FLAG_SIGNAL = 1;
	const SUB_FLAG_MESSAGE = 2;
	
	protected $commands;
	protected $handleTransactions;
	
	protected $delegateTaskFactory;
	
	protected $repositoryService;
	protected $runtimeService;
	protected $taskService;
	
	protected $executions = [];
	
	protected $engine;
	protected $pdo;
	protected $needSync = true;
	protected $needTransactionCommit = false;
	
	public function __construct(EngineInterface $engine, \PDO $pdo, $handleTransactions = true)
	{
		$this->engine = $engine;
		$this->pdo = $pdo;
		$this->handleTransactions = $handleTransactions ? true : false;
		
		$this->commands = [];
		
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
	
	public function getInternalEngine()
	{
		return $this->engine;
	}
	
	/**
	 * Create a prepared statement from the given SQL.
	 * 
	 * @param string $sql
	 * @return \PDOStatement
	 */
	public function prepareQuery($sql)
	{
		$stmt = $this->pdo->prepare($sql);
		$stmt->setFetchMode(\PDO::FETCH_ASSOC);
		
		return $stmt;
	}
	
	/**
	 * get the last ID that has been inserted / next sequence value.
	 * 
	 * @param string $seq Name of the sequence to be used.
	 * @return integer
	 */
	public function getLastInsertId($seq = NULL)
	{
		return $this->pdo->lastInsertId($seq);
	}
	
	public function executeCommand(CommandInterface $command)
	{
		$sync = $this->needSync;
		$trans = false;
		
		if($sync)
		{
			$this->needSync = false;
			
			if($this->handleTransactions && !$this->pdo->inTransaction())
			{
				$this->engine->debug('BEGIN TRANSACTION');
				
				$this->pdo->beginTransaction();
				$trans = true;
			}
		}
		
		$context = new CommandContext($this);
		
		try
		{
			while($this->executeNextCommand());
			
			$this->engine->debug('COMMAND: {0}', [get_class($command)]);
			
			$result = $command->execute($context);
			
			while($this->executeNextCommand());
			
			// Sync executions:
			if($sync)
			{
				foreach($this->executions as $info)
				{
					$this->syncExecution($info->getExecution(), $info);
				}
				
				$this->executions = [];
				
				$this->needSync = true;
				
				if($this->handleTransactions && $trans)
				{
					$this->engine->debug('COMMIT');
					
					$this->pdo->commit();
				}
			}
			
			return $result;
		}
		catch(\Exception $e)
		{
			if($sync)
			{
				$this->needSync = true;
				
				if($this->handleTransactions && $trans)
				{
					$this->engine->debug('ROLL BACK');
					
					$this->pdo->rollBack();
				}
			}
			
			throw $e;
		}
	}
	
	public function executeNextCommand()
	{
		if(empty($this->commands))
		{
			return NULL;
		}
		
		$command = array_shift($this->commands);
		
		$this->engine->debug('COMMAND [priority = {0}]: {1}', [$command->getPriority(), get_class($command)]);
		
		$command->execute(new CommandContext($this));
			
		return $command;
	}
	
	public function pushCommand(CommandInterface $command)
	{
		$priority = $command->getPriority();
		
		for($count = count($this->commands), $i = 0; $i < $count; $i++)
		{
			if($this->commands[$i]->getPriority() < $priority)
			{
				array_splice($this->commands, $i, 0, [$command]);
				
				return;
			}
		}
		
		$this->commands[] = $command;
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
	
	public function findExecution(UUID $id)
	{
		$sql = "	SELECT e.*, d.`definition`
					FROM `#__bpm_execution` AS e
					INNER JOIN `#__bpm_process_definition` AS d ON (d.`id` = e.`definition_id`)
					WHERE e.`id` = :eid
		";
		$stmt = $this->prepareQuery($sql);
		$stmt->bindValue('eid', $id->toBinary());
		$stmt->execute();
		
		if(false === ($row = $stmt->fetch(\PDO::FETCH_ASSOC)))
		{
			throw new \OutOfBoundsException(sprintf('Execution not found: %s', $id));
		}
		
		return $this->unserializeExecution($row);
	}
	
	public function registerExecution(InternalExecution $execution, array $clean = NULL)
	{
		$info = $this->executions[(string)$execution->getId()] = new ExecutionInfo($execution, $clean);
		
		$data = $this->serializeExecution($execution);
		
		if($info->getState($data) == ExecutionInfo::STATE_NEW)
		{
			$this->syncExecution($execution, $info);
		}
	}
	
	public function serializeExecution(InternalExecution $execution)
	{
		$parent = $execution->getParentExecution();
		$pid = ($parent === NULL) ? NULL : $parent->getId()->toBinary();
		$nid = ($execution->getNode() === NULL) ? NULL : $execution->getNode()->getId();
		$tid = ($execution->getTransition() === NULL) ? NULL : $execution->getTransition()->getId();
		
		return [
			'id' => $execution->getId()->toBinary(),
			'pid' => $pid,
			'process' => $execution->getProcessInstance()->getId()->toBinary(),
			'def' => $execution->getProcessDefinition()->getId()->toBinary(),
			'state' => $execution->getState(),
			'active' => $execution->getTimestamp(),
			'node' => $nid,
			'transition' => $tid,
			'bkey' => $execution->getBusinessKey(),
			'vars' => gzcompress(serialize($execution->getVariables()), 1)
		];
	}
	
	public function unserializeExecution(array $row)
	{
		$id = new UUID($row['id']);
		
		if(isset($this->executions[(string)$id]))
		{
			return $this->executions[(string)$id]->getExecution();
		}
		
		$pid = empty($row['pid']) ? NULL : new UUID($row['pid']);
		$state = (int)$row['state'];
		$active = (float)$row['active'];
		$node = empty($row['node']) ? NULL : $row['node'];
		$transition = empty($row['transition']) ? NULL : $row['transition'];
		$bkey = empty($row['business_key']) ? NULL : $row['business_key'];
		$def = unserialize(gzuncompress($row['definition']));
		$vars = unserialize(gzuncompress($row['vars']));
		
		$process = NULL;
		
		if($pid === NULL)
		{
			$execution = $process = new InternalProcessInstance($id, $this, $def, $bkey);
		}
		else
		{
			$sql = "	SELECT e.*
						FROM `#__bpm_execution` AS e
						WHERE e.`process_id` = :pid
						AND e.`id` <> :eid
						ORDER BY e.`pid` IS NOT NULL
			";
			$stmt = $this->pdo->prepare($sql);
			$stmt->bindValue('pid', $pid->toBinary());
			$stmt->bindValue('eid', $id->toBinary());
			$stmt->execute();
			
			while($inner = $stmt->fetch(\PDO::FETCH_ASSOC))
			{
				$in_id = new UUID($inner['id']);
				
				if(isset($this->executions[(string)$in_id]))
				{
					$exec = $this->executions[(string)$in_id]->getExecution();
					
					continue;
				}
				
				$in_pid = empty($inner['pid']) ? NULL : new UUID($inner['pid']);
				$in_state = (int)$inner['state'];
				$in_active = (float)$inner['active'];
				$in_node = empty($inner['node']) ? NULL : $inner['node'];
				$in_transition = empty($inner['transition']) ? NULL : $inner['transition'];
				$in_vars = unserialize(gzuncompress($inner['vars']));
				
				if($in_pid === NULL)
				{
					$exec = $process = new InternalProcessInstance($in_id, $this, $def, $bkey);
				}
				else
				{
					$exec = new InternalExecution($id, $this, $def, $process);
				}
				
				$exec->setExecutionState($in_state);
				$exec->setTimestamp($in_active);
				$exec->setVariables($in_vars);
				
				if($in_node !== NULL)
				{
					$exec->setNode($def->findNode($in_node));
				}
				
				if($in_transition !== NULL)
				{
					$exec->setTransition($def->findTransition($in_transition));
				}
				
				$this->registerExecution($exec, $this->serializeExecution($exec));
			}
			
			$execution = new InternalExecution($id, $this, $def, $process);
		}
		
		$execution->setExecutionState($state);
		$execution->setTimestamp($active);
			
		if($node !== NULL)
		{
			$execution->setNode($def->findNode($node));
		}
		
		if($transition !== NULL)
		{
			$execution->setTransition($def->findTransition($transition));
		}
		
		$execution->setVariables($vars);
		
		$this->registerExecution($execution, $this->serializeExecution($execution));
		
		return $execution;
	}
	
	protected function syncExecution(InternalExecution $execution, ExecutionInfo $info)
	{
		$data = $this->serializeExecution($execution);
		$state = $info->getState($data);
	
		if($state == ExecutionInfo::STATE_REMOVED)
		{
			$this->engine->debug('SYNC [delete]: {0}', [(string)$execution]);
			
			foreach($execution->findChildExecutions() as $child)
			{
				$this->syncExecution($child, $this->executions[(string)$child->getId()]);
			}
				
			$sql = "	DELETE FROM `#__bpm_execution`
						WHERE `id` = :id
			";
			$stmt = $this->pdo->prepare($sql);
			$stmt->bindValue('id', $data['id']);
			$stmt->execute();
				
			unset($this->executions[(string)$execution->getId()]);
				
			return;
		}
	
		if($state == ExecutionInfo::STATE_MODIFIED)
		{
			$this->engine->debug('SYNC [update]: {0}', [(string)$execution]);
			
			$sql = "	UPDATE `#__bpm_execution`
						SET `state` = :state,
							`active` = :active,
							`node` = :node,
							`transition` = :transition,
							`vars` = :vars
						WHERE `id` = :id
			";
			$stmt = $this->pdo->prepare($sql);
			$stmt->bindValue('id', $data['id']);
			$stmt->bindValue('state', $data['state']);
			$stmt->bindValue('active', $data['active']);
			$stmt->bindValue('node', $data['node']);
			$stmt->bindValue('transition', $data['transition']);
			$stmt->bindValue('vars', $data['vars']);
			$stmt->execute();
				
			$info->update($data);
		}
		elseif($state == ExecutionInfo::STATE_NEW)
		{
			$this->engine->debug('SYNC [create]: {0}', [(string)$execution]);
			
			$sql = "	INSERT INTO `#__bpm_execution`
							(`id`, `pid`, `process_id`, `definition_id`, `state`, `active`, `node`, `transition`, `business_key`, `vars`)
						VALUES
							(:id, :pid, :process, :def, :state, :active, :node, :transition, :bkey, :vars)
			";
			$stmt = $this->pdo->prepare($sql);
			$stmt->execute($data);
				
			$info->update($data);
		}
	
		foreach($execution->findChildExecutions() as $child)
		{
			$this->syncExecution($child, $this->executions[(string)$child->getId()]);
		}
	}
}
