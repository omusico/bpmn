<?php

/*
 * This file is part of KoolKode BPMN.
*
* (c) Martin Schröder <m.schroeder2007@gmail.com>
*
* For the full copyright and license information, please view the LICENSE
* file that was distributed with this source code.
*/

namespace KoolKode\BPMN\Command;

use KoolKode\BPMN\CommandContext;
use KoolKode\BPMN\InternalExecution;

class SignalExecutionCommand extends AbstractCommand
{
	protected $execution;
	
	protected $signal;
	
	protected $variables;
	
	public function __construct(InternalExecution $execution, $signal = NULL, array $variables = [])
	{
		$this->execution = $execution;
		$this->signal = ($signal === NULL) ? NULL : (string)$signal;
		$this->variables = $variables;
	}
	
	public function execute(CommandContext $context)
	{
		$this->execution->signal($this->signal, $this->variables);
	}
}
