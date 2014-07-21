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
		$id = $execution->getNode()->getId();
		$parent = $execution->getParentExecution();	
		$vars = ($parent === NULL) ? $execution->getVariables() : $parent->getVariables();
		
		$execution->getProcessEngine()->pushCommand(new ThrowMessageCommand($execution->getId(), $id, $vars));
		$execution->takeAll(NULL, [$execution]);
	}
}
