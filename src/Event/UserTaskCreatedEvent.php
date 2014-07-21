<?php

/*
 * This file is part of KoolKode BPMN.
*
* (c) Martin Schröder <m.schroeder2007@gmail.com>
*
* For the full copyright and license information, please view the LICENSE
* file that was distributed with this source code.
*/

namespace KoolKode\BPMN\Event;

use KoolKode\BPMN\ProcessEngine;
use KoolKode\BPMN\TaskInterface;

class UserTaskCreatedEvent
{
	public $task;
	
	public $engine;
	
	public function __construct(TaskInterface $task, ProcessEngine $engine)
	{
		$this->task = $task;
		$this->engine = $engine;
	}
}
