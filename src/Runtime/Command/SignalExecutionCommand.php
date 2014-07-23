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

class SignalExecutionCommand extends AbstractBusinessCommand
{
	protected $execution;
	
	protected $signal;
	
	protected $variables;
	
	public function __construct(VirtualExecution $execution, $signal = NULL, array $variables = [])
	{
		$this->execution = $execution;
		$this->signal = ($signal === NULL) ? NULL : (string)$signal;
		$this->variables = $variables;
	}
	
	public function executeCommand(ProcessEngine $engine)
	{
		$engine->debug('Signaling execution {execution} using signal {signal}', [
			'execution' => (string)$this->execution->getId(),
			'signal' => $this->signal
		]);
		
		$this->execution->signal($this->signal, $this->variables);
	}
}
