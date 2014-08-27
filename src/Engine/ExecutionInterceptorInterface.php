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
 * Execution interceptors are applied around bulk execution of commands within the process engine.
 * 
 * @author Martin Schröder
 */
interface ExecutionInterceptorInterface
{
	/**
	 * Get the priority of this interceptor.
	 * 
	 * @return integer
	 */
	public function getPriority();
	
	/**
	 * Apply interceptor code and delegate to the actual execution (or the next interceptor).
	 * 
	 * @param ExecutionInterceptorChain $chain
	 * @param integer $executionDepth
	 * @return mixed
	 */
	public function interceptExecution(ExecutionInterceptorChain $chain, $executionDepth);
}
