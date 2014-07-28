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
use KoolKode\Expression\ExpressionInterface;

class DelegateTaskBehavior extends AbstractBehavior
{
	protected $typeName;
	
	protected $name;
	
	public function __construct(ExpressionInterface $typeName, ExpressionInterface $name)
	{
		$this->typeName = $typeName;
		$this->name = $name;
	}
	
	public function executeBehavior(VirtualExecution $execution)
	{
		$typeName = (string)call_user_func($this->typeName, $execution->getExpressionContext());
		$task = $execution->getEngine()->createDelegateTask($typeName);
		
		if($task instanceof DelegateTaskInterface)
		{
			$execution->getEngine()->debug('Execute delegate task "{task}" implemented by {class}', [
				'task' => (string)call_user_func($this->name, $execution->getExpressionContext()),
				'class' => get_class($task)
			]);
			
			$task->execute(new DelegateExecution($execution));
			
			return $execution->takeAll(NULL, [$execution]);
		}
		
		throw new \RuntimeException('Invalid service task implementation: ' . get_class($task));
	}
}
