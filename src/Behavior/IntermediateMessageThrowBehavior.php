<?php

namespace KoolKode\BPMN\Behavior;

use KoolKode\BPMN\Command\ThrowMessageCommand;
use KoolKode\BPMN\ProcessEngine;
use KoolKode\Process\ActivityInterface;
use KoolKode\Process\Execution;

class IntermediateMessageThrowBehavior implements ActivityInterface
{
	public function execute(Execution $execution)
	{
		$pid = $execution->getProcessInstance()->getId();
		$id = $execution->getNode()->getId();
		$parent = $execution->getParentExecution();
		
		$vars = ($parent === NULL) ? $execution->getVariables() : $parent->getVariables();
		
		$command = new ThrowMessageCommand($pid, $id, $vars);
		$execution->getProcessEngine()->pushCommand($command);
		
		$execution->takeAll(NULL, [$execution]);
	}
}
