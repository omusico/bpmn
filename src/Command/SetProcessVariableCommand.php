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
use KoolKode\Util\Uuid;

class SetProcessVariableCommand extends AbstractCommand
{
	protected $executionId;
	protected $variableName;
	protected $variableValue;
	
	public function __construct(UUID $executionId, $variableName, $variableValue)
	{
		$this->executionId = $executionId;
		$this->variableName = (string)$variableName;
		$this->variableValue = $variableValue;
	}
	
	public function execute(CommandContext $context)
	{
		$execution = $context->loadExecution($this->executionId);
		$execution->setVariable($this->variableName, $this->variableValue);
	}
}
