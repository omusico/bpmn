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

use KoolKode\Process\Execution;
use KoolKode\Process\ProcessDefinition;
use KoolKode\Process\Transition;
use KoolKode\Util\Uuid;

class VirtualExecution extends Execution
{
	protected $businessKey;
	
	public function __construct(UUID $id, ProcessEngine $engine, ProcessDefinition $processDefinition, VirtualExecution $parentExecution = NULL)
	{
		parent::__construct($id, $engine, $processDefinition, $parentExecution);
		
		if($parentExecution !== NULL)
		{
			$this->businessKey = $parentExecution->getBusinessKey();
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
}
