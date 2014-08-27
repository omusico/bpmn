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

/**
 * Interceptor chain that organizes and invokes interceptors around an execution.
 * 
 * @author Martin Schröder
 */
class ExecutionInterceptorChain
{
	protected $callback;
	
	protected $executionDepth;
	
	protected $interceptors;
	
	public function __construct(callable $callback, $executionDepth, array $interceptors = [])
	{
		$this->callback = $callback;
		$this->executionDepth = (int)$executionDepth;
		$this->interceptors = new \SplPriorityQueue();
		
		foreach($interceptors as $interceptor)
		{
			$this->interceptors->insert($interceptor, $interceptor->getPriority());
		}
	}
	
	/**
	 * Delegate to the next interceptor or actually perform the queued execution.
	 * 
	 * @return mixed The result of the command execution.
	 */
	public function performExecution()
	{
		if(!$this->interceptors->isEmpty())
		{
			$interceptor = $this->interceptors->extract();
			
			return $interceptor->interceptExecution($this, $this->executionDepth);
		}
		
		return call_user_func($this->callback);
	}
}
