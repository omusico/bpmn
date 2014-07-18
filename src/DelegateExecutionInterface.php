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

use KoolKode\Expression\ExpressionContextInterface;
use KoolKode\Util\UUID;

/**
 * Exposes an API for an execution to delegate task implementations, delegate tasks may read / write
 * execution / process data (variables, business key, etc) but must not take control of 
 * execution flow (transitions and signals).
 * 
 * @author Martin Schröder
 */
interface DelegateExecutionInterface
{
	/**
	 * Get the unique identifier of the underlying execution.
	 * 
	 * @return UUID
	 */
	public function getExecutionId();
	
	/**
	 * Get the unique identifier of the underlying process instance.
	 * 
	 * @return UUID
	 */
	public function getProcessInstanceId();
	
	/**
	 * Get the business key of the underlying process instance.
	 * 
	 * @return string or NULL when no business key is set.
	 */
	public function getBusinessKey();
	
	/**
	 * Create an expression context bound to the underlying execution.
	 * 
	 * @return ExpressionContextInterface
	 */
	public function getExpressionContext();
	
	/**
	 * Check for existance of the given variabel in the current scope.
	 * 
	 * @param string $name
	 * @return boolean
	 */
	public function hasVariable($name);
	
	/**
	 * Get the value of the given variable from the current scope.
	 * 
	 * @param string $name
	 * @param mixed $default
	 * @return mixed
	 * 
	 * @throws \OutOfBoundException When the variable is not found an no default value is given.
	 */
	public function getVariable($name);
	
	/**
	 * Set the value of the given variable in the current scope.
	 * 
	 * @param string $name
	 * @param mixed $value
	 */
	public function setVariable($name, $value);
	
	/**
	 * Remove the given variable from the current scope.
	 * 
	 * @param string $name
	 */
	public function removeVariable($name);
}
