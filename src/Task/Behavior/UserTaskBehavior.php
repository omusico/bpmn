<?php

/*
 * This file is part of KoolKode BPMN.
*
* (c) Martin Schröder <m.schroeder2007@gmail.com>
*
* For the full copyright and license information, please view the LICENSE
* file that was distributed with this source code.
*/

namespace KoolKode\BPMN\Task\Behavior;

use KoolKode\BPMN\Engine\AbstractSignalableBehavior;
use KoolKode\BPMN\Engine\VirtualExecution;
use KoolKode\BPMN\Task\Command\CreateUserTaskCommand;

class UserTaskBehavior extends AbstractSignalableBehavior
{
	protected $name;
	
	public function __construct($name = '')
	{
		$this->name = trim($name);
	}
	
	public function executeBehavior(VirtualExecution $execution)
	{
		$execution->waitForSignal();
		$execution->getEngine()->pushCommand(new CreateUserTaskCommand($this->name, $execution));
	}
}
