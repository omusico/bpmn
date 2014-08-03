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

use KoolKode\BPMN\Engine\ProcessEngineInterface;
use KoolKode\BPMN\Task\TaskInterface;
use KoolKode\BPMN\Engine\ProcessEngine;

/**
 * Is triggered whenever a user task has been completed successfully.
 * 
 * @author Martin Schröder
 */
class UserTaskCompletedEvent
{
	/**
	 * The task being completed.
	 * 
	 * @var TaskInterface
	 */
	public $task;
	
	/**
	 * Provides access to the process engine.
	 * 
	 * @var ProcessEngine
	 */
	public $engine;
	
	public function __construct(TaskInterface $task, ProcessEngineInterface $engine)
	{
		$this->task = $task;
		$this->engine = $engine;
	}
}
