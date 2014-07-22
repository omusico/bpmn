<?php

/*
 * This file is part of KoolKode BPMN.
*
* (c) Martin Schröder <m.schroeder2007@gmail.com>
*
* For the full copyright and license information, please view the LICENSE
* file that was distributed with this source code.
*/

namespace KoolKode\BPMN\Behavior;

use KoolKode\BPMN\Command\CreateUserTaskCommand;
use KoolKode\Process\Behavior\SignalableBehaviorInterface;
use KoolKode\Process\Execution;

class UserTaskBehavior implements SignalableBehaviorInterface
{
	protected $name;
	
	public function __construct($name = '')
	{
		$this->name = trim($name);
	}
	
	public function execute(Execution $execution)
	{ 
		$execution->waitForSignal();
		$execution->getProcessEngine()->pushCommand(new CreateUserTaskCommand($this->name, $execution));
	}
	
	public function signal(Execution $execution, $signal, array $variables = [])
	{
		foreach($variables as $k => $v)
		{
			$execution->setVariable($k, $v);
		}
		
		return $execution->takeAll(NULL, [$execution]);
	}
}
