<?php

/*
 * This file is part of KoolKode BPMN.
*
* (c) Martin Schröder <m.schroeder2007@gmail.com>
*
* For the full copyright and license information, please view the LICENSE
* file that was distributed with this source code.
*/

namespace KoolKode\BPMN\Task\Event;

use KoolKode\BPMN\Engine\ProcessEngine;
use KoolKode\BPMN\Engine\ProcessEngineEvent;
use KoolKode\BPMN\Task\TaskInterface;

/**
 * Is triggered whenever a user task has been claimed.
 * 
 * @author Martin Schröder
 */
class UserTaskClaimedEvent extends ProcessEngineEvent
{
	/**
	 * The task being claimed.
	 * 
	 * @var TaskInterface
	 */
	public $task;
	
	public function __construct(TaskInterface $task, ProcessEngine $engine)
	{
		$this->task = $task;
		$this->engine = $engine;
	}
}
