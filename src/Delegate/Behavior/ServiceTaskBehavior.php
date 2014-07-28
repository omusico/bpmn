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

use KoolKode\BPMN\Delegate\Event\ServiceTaskExecutedEvent;
use KoolKode\BPMN\Engine\AbstractBehavior;
use KoolKode\BPMN\Engine\VirtualExecution;
use KoolKode\Expression\ExpressionInterface;
use KoolKode\BPMN\Delegate\DelegateExecution;

class ServiceTaskBehavior extends AbstractBehavior
{
	protected $name;
	
	public function __construct(ExpressionInterface $name)
	{
		$this->name = $name;
	}
	
	public function executeBehavior(VirtualExecution $execution)
	{
		$execution->getEngine()->debug('Executing service task "{task}"', [
			'task' => (string)call_user_func($this->name, $execution->getExpressionContext())
		]);
		
		$execution->getEngine()->notify(new ServiceTaskExecutedEvent(
			new DelegateExecution($execution),
			$execution->getEngine()
		));
		
		$execution->takeAll(NULL, [$execution]);
	}
}
