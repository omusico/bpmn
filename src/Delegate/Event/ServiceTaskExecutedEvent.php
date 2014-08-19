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
	/**
	 * Name of the task being executed.
	 * 
	 * @var string
	 */
	public $name;
	
	/**
	 * Provides access to the execution that triggered the service task.
	 * 
	 * @var DelegateExecutionInterface
	 */
	public $execution;
	
	/**
	 * Provides access to the process engine.
	 * 
	 * @var ProcessEngine
	 */
	public $engine;
	
	public function __construct($name, DelegateExecutionInterface $execution, ProcessEngine $engine)
	{
		$this->name = (string)$name;
		$this->execution = $execution;
		$this->engine = $engine;
	}
}
