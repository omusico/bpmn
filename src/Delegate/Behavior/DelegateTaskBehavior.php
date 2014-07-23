<?php

/*
 * This file is part of KoolKode BPMN.
*
* (c) Martin Schröder <m.schroeder2007@gmail.com>
*
* For the full copyright and license information, please view the LICENSE
* file that was distributed with this source code.
*/

namespace KoolKode\BPMN\Delegate\Behavior;

use KoolKode\BPMN\Delegate\DelegateExecution;
use KoolKode\BPMN\Delegate\DelegateTaskInterface;
use KoolKode\BPMN\Engine\AbstractBehavior;
use KoolKode\BPMN\Engine\VirtualExecution;

class DelegateTaskBehavior extends AbstractBehavior
{
	protected $typeName;
	
	public function __construct($typeName)
	{
		$this->typeName = (string)$typeName;
	}
	
	public function executeBehavior(VirtualExecution $execution)
	{
		$task = $execution->getEngine()->createDelegateTask($this->typeName);
		
		if($task instanceof DelegateTaskInterface)
		{
			$execution->getEngine()->debug('Execute delegate task {task}', [
				'task' => get_class($task)
			]);
			
			$task->execute(new DelegateExecution($execution));
			
			return $execution->takeAll(NULL, [$execution]);
		}
		
		throw new \RuntimeException('Invalid service task implementation: ' . get_class($task));
	}
}
