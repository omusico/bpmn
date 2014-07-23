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

class ExecutionInfo
{
	const STATE_NONE = 0;
	const STATE_NEW = 1;
	const STATE_MODIFIED = 2;
	const STATE_REMOVED = 3;
	
	protected $execution;
	protected $clean;
	
	public function __construct(VirtualExecution $execution, array $clean = NULL)
	{
		$this->execution = $execution;
		$this->clean = $clean;
	}
	
	public function getExecution()
	{
		return $this->execution;
	}
	
	public function update(array $clean = NULL)
	{
		$this->clean = $clean;
	}
	
	public function getState(array $data)
	{
		if($this->execution->isTerminated())
		{
			return ($this->clean === NULL) ? self::STATE_NONE : self::STATE_REMOVED;
		}
		
		if($this->clean === NULL)
		{
			return self::STATE_NEW;
		}
		
		if($this->clean != $data)
		{
			return self::STATE_MODIFIED;
		}
		
		return self::STATE_NONE;
	}
}
