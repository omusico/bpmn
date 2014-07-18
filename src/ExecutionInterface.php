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

use KoolKode\Util\UUID;

/**
 * Each token in a BPMN 2.0 process is modeled as an execution that is part of the
 * execution hierarchy of a process.
 * 
 * @author Martin Schröder
 */
interface ExecutionInterface
{
	/**
	 * Get the unique identifier of this execution.
	 * 
	 * @return UUID
	 */
	public function getId();

	/**
	 * Get the unique identifiere of the parent execution of this execution.
	 * 
	 * @return UUID Unique identifier of the parent execution or NULL if there is no such execution.
	 */
	public function getParentId();
	
	/**
	 * Get the unique identifier of the process that is the top-level execution of this execution.
	 * 
	 * @return UUID
	 */
	public function getProcessInstanceId();
	
	/**
	 * Get the identifier (as defined by the "id" attribute in a BPMN 2.0 process diagram) of the
	 * activity that is currently being executed by this execution.
	 * 
	 * @return string Identifier of activity being executed or NULL when the execution has terminated.
	 */
	public function getActivityId();
	
	/**
	 * Check if this execution has been teminated.
	 * 
	 * @return boolean
	 */
	public function isEnded();
}
