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

use KoolKode\BPMN\Runtime\Command\SignalExecutionCommand;
use KoolKode\Process\Execution;
use KoolKode\Process\ProcessModel;
use KoolKode\Process\Transition;
use KoolKode\Util\UUID;

/**
 * PVM execution being used to automate BPMN 2.0 processes.
 * 
 * @author Martin Schröder
 */
class VirtualExecution extends Execution
{
	protected $businessKey;
	
	public function __construct(UUID $id, ProcessEngine $engine, ProcessModel $model, VirtualExecution $parentExecution = NULL)
	{
		parent::__construct($id, $engine, $model, $parentExecution);
		
		if($parentExecution !== NULL)
		{
			$this->businessKey = $parentExecution->getBusinessKey();
		}
	}
	
	public function setParentExecution(VirtualExecution $parent = NULL)
	{
		if($parent !== NULL)
		{		
			$this->parentExecution = $parent;
			
			$parent->registerChildExecution($this);
		}
	}
	
	/**
	 * Get the BPMN process engine instance.
	 * 
	 * @return ProcessEngine
	 */
	public function getEngine()
	{
		return parent::getEngine();
	}
	
	public function getBusinessKey()
	{
		return $this->businessKey;
	}
	
	public function setBusinessKey($businessKey = NULL)
	{
		$this->businessKey = ($businessKey === NULL) ? NULL : (string)$businessKey;
	}
	
	public function getExecutionDepth()
	{
		if($this->parentExecution === NULL)
		{
			return 0;
		}
		
		return $this->parentExecution->getExecutionDepth() + 1;
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
	
	public function terminate($triggerExecution = true)
	{
		parent::terminate($triggerExecution);
		
		$this->engine->syncExecutionState($this);
	}
	
	public function setActive($active)
	{
		parent::setActive($active);
		
		$this->engine->syncExecutionState($this);
	}
	
	public function waitForSignal()
	{
		parent::waitForSignal();
		
		$this->engine->syncExecutionState($this);
	}
}
