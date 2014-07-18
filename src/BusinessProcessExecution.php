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

class BusinessProcessExecution
{
	protected $execution;
	
	public function __construct(InternalExecution $execution)
	{
		$this->execution = $execution;
	}
	
	public function getInternalExecution()
	{
		return $this->execution;
	}
	
	public function getProcessEngine()
	{
		return $this->execution->getProcessEngine();
	}
	
	public function isActive()
	{
		return $this->execution->isActive();
	}
	
	public function isConcurrent()
	{
		return $this->execution->isConcurrent();
	}
	
	public function isScope()
	{
		return $this->execution->isScope();
	}
	
	public function isTerminated()
	{
		return $this->execution->isTerminated();
	}
	
	public function isWaiting()
	{
		return $this->execution->isWaiting();
	}
	
	public function waitForSignal()
	{
		$this->execution->waitForSignal();
	}
	
	public function take($transition = NULL)
	{
		$this->execution->take($transition);	
	}
	
	public function takeAll(array $transitions = NULL, array $recycle = [])
	{
		$this->execution->takeAll($transitions, $recycle);
	}
	
	public function hasVariable($name)
	{
		return $this->execution->hasVariable($name);
	}
	
	public function getVariable($name)
	{
		if(func_num_args() > 1)
		{
			return $this->execution->getVariable($name, func_get_arg(1));
		}
		
		return $this->execution->getVariable($name);
	}
	
	public function setVariable($name, $value)
	{
		$this->execution->setVariable($name, $value);
	}
	
	public function removeVariable($name)
	{
		$this->execution->removeVariable($name);
	}
}
