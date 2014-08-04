<?php

/*
 * This file is part of KoolKode BPMN.
*
* (c) Martin Schröder <m.schroeder2007@gmail.com>
*
* For the full copyright and license information, please view the LICENSE
* file that was distributed with this source code.
*/

namespace KoolKode\BPMN\Runtime\Behavior;

use KoolKode\BPMN\Engine\AbstractSignalableBehavior;
use KoolKode\BPMN\Engine\VirtualExecution;
use KoolKode\BPMN\Runtime\Command\NotifyCheckpointCommand;

/**
 * None events are used as checkpoints within a process.
 * 
 * @author Martin Schröder
 */
class IntermediateNoneEventBehavior extends AbstractSignalableBehavior
{
	public function executeBehavior(VirtualExecution $execution)
	{
		$execution->getEngine()->pushCommand(new NotifyCheckpointCommand(
			$this->getStringValue($this->name, $execution->getExpressionContext()),	
			$execution
		));
		
		return parent::executeBehavior($execution);
	}
}
