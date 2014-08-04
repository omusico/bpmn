<?php

/*
 * This file is part of KoolKode BPMN.
*
* (c) Martin Schröder <m.schroeder2007@gmail.com>
*
* For the full copyright and license information, please view the LICENSE
* file that was distributed with this source code.
*/

namespace KoolKode\BPMN\Runtime\Command;

use KoolKode\BPMN\Engine\AbstractBusinessCommand;
use KoolKode\BPMN\Engine\ProcessEngine;
use KoolKode\BPMN\Engine\VirtualExecution;
use KoolKode\BPMN\Runtime\Event\CheckpointReachedEvent;

/**
 * Notifies event listeners when a checkpoint within a process has been reached.
 * 
 * @author Martin Schröder
 */
class NotifyCheckpointCommand extends AbstractBusinessCommand
{
	protected $name;
	
	protected $execution;
	
	public function __construct($name, VirtualExecution $execution)
	{
		$this->name = (string)$name;
		$this->execution = $execution;
	}
	
	public function executeCommand(ProcessEngine $engine)
	{
		$execution = $engine->getRuntimeService()
							->createExecutionQuery()
							->executionId($this->execution->getId())
							->findOne();
		
		$engine->pushCommand(new SignalExecutionCommand($this->execution));
		
		$engine->debug('{execution} reached checkpoint "{checkpoint}" ({node})', [
			'execution' => (string)$this->execution,
			'checkpoint' => $this->name,
			'node' => $this->execution->getNode()->getId()
		]);
		
		$engine->notify(new CheckpointReachedEvent($this->name, $execution, $engine));
	}
}
