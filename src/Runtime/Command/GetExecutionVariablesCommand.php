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
use KoolKode\Util\Uuid;

/**
 * Populates a local variable in an execution.
 * 
 * @author Martin Schröder
 */
class GetExecutionVariablesCommand extends AbstractBusinessCommand
{
	protected $executionId;
	
	public function __construct(UUID $executionId)
	{
		$this->executionId = $executionId;
	}
	
	public function executeCommand(ProcessEngine $engine)
	{
		return $engine->findExecution($this->executionId)->getVariablesLocal();
	}
}
