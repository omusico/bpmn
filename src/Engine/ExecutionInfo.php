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
	
	public function __debugInfo()
	{
		return [
			'execution' => (string)$this->execution->getId()
		];
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
	
	public function getVariableDelta(array $data)
	{
		$result = [
			self::STATE_REMOVED => [],
			self::STATE_NEW => []
		];
		
		foreach((array)$this->clean['vars'] as $k => $v)
		{
			if(!array_key_exists($k, $data))
			{
				$result[self::STATE_REMOVED][$k] = true;
				
				unset($data[$k]);
				
				continue;
			}
			
			if($v !== $data[$k])
			{
				$result[self::STATE_REMOVED][$k] = true;
				$result[self::STATE_NEW][$k] = true;
			}
			
			unset($data[$k]);
		}
		
		foreach($data as $k => $v)
		{
			$result[self::STATE_NEW][$k] = true;
		}
		
		$result[self::STATE_REMOVED] = array_keys($result[self::STATE_REMOVED]);
		$result[self::STATE_NEW] = array_keys($result[self::STATE_NEW]);
		
		return $result;
	}
}
