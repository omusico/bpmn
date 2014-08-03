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
use KoolKode\BPMN\Runtime\Event\MessageThrownEvent;

/**
 * Notifies event listeners when a message throw event has been executed.
 * 
 * @author Martin Schröder
 */
class ThrowMessageCommand extends AbstractBusinessCommand
{
	protected $execution;
	
	public function __construct(VirtualExecution $execution)
	{
		$this->execution = $execution;
	}
	
	public function getPriority()
	{
		return self::PRIORITY_DEFAULT - 500;
	}
	
	public function executeCommand(ProcessEngine $engine)
	{
		$execution = $engine->getRuntimeService()
							->createExecutionQuery()
							->executionId($this->execution->getId())
							->findOne();
		
		// Signal execution before event notification, needed to make sure event / message subscriptions
		// in the throwing process are created in time.
		$engine->pushCommand(new SignalExecutionCommand($this->execution));
		
		$engine->notify(new MessageThrownEvent($execution, $engine));
	}
}
