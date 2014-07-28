<?php

/*
 * This file is part of KoolKode BPMN.
*
* (c) Martin Schröder <m.schroeder2007@gmail.com>
*
* For the full copyright and license information, please view the LICENSE
* file that was distributed with this source code.
*/

namespace KoolKode\BPMN\Delegate\Event;

use KoolKode\BPMN\Delegate\DelegateExecutionInterface;
use KoolKode\BPMN\Engine\ProcessEngine;
use KoolKode\Util\Uuid;

/**
 * Is triggered whenever a service task without specific implementation is being executed.
 * 
 * @author Martin Schröder
 */
class ServiceTaskExecutedEvent
{
	public $execution;
	
	public $engine;
	
	public function __construct(DelegateExecutionInterface $execution, ProcessEngine $engine)
	{
		$this->execution = $execution;
		$this->engine = $engine;
	}
}
