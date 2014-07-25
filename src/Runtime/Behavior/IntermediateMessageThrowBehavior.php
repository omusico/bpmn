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
use KoolKode\BPMN\Runtime\Command\ThrowMessageCommand;

class IntermediateMessageThrowBehavior extends AbstractSignalableBehavior
{
	public function executeBehavior(VirtualExecution $execution)
	{
		$execution->waitForSignal();
		$execution->getEngine()->pushCommand(new ThrowMessageCommand($execution));
	}
}
