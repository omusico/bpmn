<?php

/*
 * This file is part of KoolKode BPMN.
*
* (c) Martin Schröder <m.schroeder2007@gmail.com>
*
* For the full copyright and license information, please view the LICENSE
* file that was distributed with this source code.
*/

namespace KoolKode\BPMN\Task;

use KoolKode\Util\UUID;

/**
 * represents a user task instance.
 * 
 * @author Martin Schröder
 */
interface TaskInterface
{
	/**
	 * Get the unique identifier of this user task instance.
	 * 
	 * @return UUID
	 */
	public function getId();

	/**
	 * Get the unique identifier of the execution that triggered the task instance.
	 * 
	 * @return UUID
	 */
	public function getExecutionId();
	
	/**
	 * Get the name (as defined in a BPMN 2.0 process diagram) of the activity to be performed.
	 * 
	 * @return string
	 */
	public function getName();
	
	/**
	 * Get the identifier (as defined by the "id" attribute in a BPMN 2.0 diagram) of the
	 * activity to be performed.
	 * 
	 * @return string
	 */
	public function getActivityId();

	/**
	 * Get the time of creation of this activity instance.
	 * 
	 * @return \DateTime
	 */
	public function getCreated();
	
	/**
	 * Get the assignment date of this task.
	 * 
	 * @return \DateTime or NULL when the task instance has not been claimed yet.
	 */
	public function getClaimDate();
	
	/**
	 * Get the identity of the assignee of this task.
	 * 
	 * @return string or NULL when the task instance has not been claimed yet.
	 */
	public function getAssignee();
}
