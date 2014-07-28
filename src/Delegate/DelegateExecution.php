<?php

/*
 * This file is part of KoolKode BPMN.
*
* (c) Martin Schröder <m.schroeder2007@gmail.com>
*
* For the full copyright and license information, please view the LICENSE
* file that was distributed with this source code.
*/

namespace KoolKode\BPMN\Delegate;

use KoolKode\BPMN\Engine\VirtualExecution;

class DelegateExecution implements DelegateExecutionInterface
{
	protected $execution;
	
	public function __construct(VirtualExecution $execution)
	{
		$this->execution = $execution;
	}
	
	public function getExecutionId()
	{
		return $this->execution->getId();
	}
	
	public function getActivityId()
	{
		$node = $this->execution->getNode();
		
		return ($node === NULL) ? NULL : $node->getId();
	}
	
	public function getProcessInstanceId()
	{
		return $this->execution->getRootExecution()->getId();
	}
	
	public function getBusinessKey()
	{
		return $this->execution->getBusinessKey();
	}
	
	public function getExpressionContext()
	{
		return $this->execution->getExpressionContext();
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
