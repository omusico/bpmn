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

use KoolKode\BPMN\DelegateExecution;
use KoolKode\BPMN\DelegateTaskInterface;
use KoolKode\Process\ActivityInterface;
use KoolKode\Process\Execution;

class DelegateTaskBehavior implements ActivityInterface
{
	protected $typeName;
	
	public function __construct($typeName)
	{
		$this->typeName = (string)$typeName;
	}
	
	public function execute(Execution $execution)
	{
		// FIXME: Create a factory component that can be decoupled from the DI container.
		
		$container = $execution->getEngine()->getContainer();
		$task = $container->get($this->typeName);
		
		if($task instanceof DelegateTaskInterface)
		{
			$task->execute(new DelegateExecution($execution));
			
			return $execution->takeAll(NULL, [$execution]);
		}
		
		throw new \RuntimeException('Invalid service task implementation: ' . get_class($task));
	}
}
