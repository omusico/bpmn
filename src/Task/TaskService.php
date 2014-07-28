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

use KoolKode\BPMN\Engine\ProcessEngine;
use KoolKode\BPMN\Task\Command\ClaimUserTaskCommand;
use KoolKode\BPMN\Task\Command\CompleteUserTaskCommand;
use KoolKode\BPMN\Task\Command\UnclaimUserTaskCommand;
use KoolKode\Util\Uuid;

class TaskService
{
	protected $engine;
	
	public function __construct(ProcessEngine $engine)
	{
		$this->engine = $engine;
	}
	
	public function createTaskQuery()
	{
		return new TaskQuery($this->engine);
	}
	
	public function claim(UUID $taskId, $userId)
	{
		$this->engine->pushCommand(new ClaimUserTaskCommand($taskId, $userId));
	}
	
	public function unclaim(UUID $taskId)
	{
		$this->engine->pushCommand(new UnclaimUserTaskCommand($taskId));
	}
	
	public function complete(UUID $taskId, array $variables = [])
	{
		$this->engine->pushCommand(new CompleteUserTaskCommand($taskId, $variables));
	}
}
