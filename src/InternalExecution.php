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

use KoolKode\Process\Execution;
use KoolKode\Process\ProcessDefinition;
use KoolKode\Process\Transition;
use KoolKode\Util\Uuid;

class InternalExecution extends Execution
{
	protected $businessKey;
	
	protected $processEngine;
	
	public function __construct(UUID $id, ProcessEngine $processEngine, ProcessDefinition $processDefinition, InternalExecution $parentExecution)
	{
		parent::__construct($id, $processEngine->getInternalEngine(), $processDefinition, $parentExecution);
		
		$this->businessKey = $parentExecution->getBusinessKey();
		$this->processEngine = $processEngine;
	}
	
	public function getProcessEngine()
	{
		return $this->processEngine;
	}
	
	public function getBusinessKey()
	{
		return $this->businessKey;
	}
	
	public function setExecutionState($state)
	{
		$this->state = (int)$state;
	}
	
	public function setTimestamp($timestamp)
	{
		$this->timestamp = (float)$timestamp;
	}
	
	public function setTransition(Transition $trans = NULL)
	{
		$this->transition = $trans;
	}
	
	public function createExecution($concurrent = true)
	{
		$execution = new InternalExecution(UUID::createRandom(), $this->processEngine, $this->processDefinition, $this);
		$execution->setNode($this->node);
	
		if($concurrent)
		{
			$execution->state |= self::STATE_CONCURRENT;
		}
		
		$this->processEngine->registerExecution($execution);
	
		return $this->childExecutions[] = $execution;
	}
	
	protected function processCreatedExecution(Execution $execution)
	{
		parent::processCreatedExecution($execution);
		
		if($execution instanceof self)
		{
			$execution->businessKey = $this->businessKey;
		}
	}
}
