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

/**
 * Is triggered whenever a user task has been completed successfully.
 * 
 * @author Martin Schröder
 */
class UserTaskCompletedEvent
{
	public $task;
	
	public $engine;
	
	public function __construct(TaskInterface $task, ProcessEngineInterface $engine)
	{
		$this->task = $task;
		$this->engine = $engine;
	}
}
