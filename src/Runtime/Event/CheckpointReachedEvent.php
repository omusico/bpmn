<?php

/*
 * This file is part of KoolKode BPMN.
*
* (c) Martin Schröder <m.schroeder2007@gmail.com>
*
* For the full copyright and license information, please view the LICENSE
* file that was distributed with this source code.
*/

namespace KoolKode\BPMN\Runtime\Event;

use KoolKode\BPMN\Engine\ProcessEngine;
use KoolKode\BPMN\Runtime\ExecutionInterface;

/**
 * Is triggered whenever a specific checkpoint within a process has been reached.
 * 
 * @author Martin Schröder
 */
class CheckpointReachedEvent
{
	/**
	 * Name of the element that triggered the checkpoint.
	 * 
	 * @var string
	 */
	public $name;
	
	/**
	 * The execution throwing the message.
	 * 
	 * @var ExecutionInterface
	 */
	public $execution;
	
	/**
	 * Provides access to the process engine.
	 * 
	 * @var ProcessEngine
	 */
	public $engine;
	
	public function __construct($name, ExecutionInterface $execution, ProcessEngine $engine)
	{
		$this->name = (string)$name;
		$this->execution = $execution;
		$this->engine = $engine;
	}
}
