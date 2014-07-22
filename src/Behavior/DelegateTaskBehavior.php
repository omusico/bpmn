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
use KoolKode\Process\Behavior\BehaviorInterface;
use KoolKode\Process\Execution;

class DelegateTaskBehavior implements BehaviorInterface
{
	protected $typeName;
	
	public function __construct($typeName)
	{
		$this->typeName = (string)$typeName;
	}
	
	public function execute(Execution $execution)
	{
		$task = $execution->getProcessEngine()->createDelegateTask($this->typeName);
		
		if($task instanceof DelegateTaskInterface)
		{
			$task->execute(new DelegateExecution($execution));
			
			return $execution->takeAll(NULL, [$execution]);
		}
		
		throw new \RuntimeException('Invalid service task implementation: ' . get_class($task));
	}
}
