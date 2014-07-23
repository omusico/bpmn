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
use KoolKode\BPMN\Runtime\Command\CreateSignalSubscriptionCommand;

class IntermediateSignalCatchBehavior extends AbstractSignalableBehavior
{
	protected $signal;
	
	public function __construct($signal)
	{
		$this->signal = (string)$signal;
	}
	
	public function executeBehavior(VirtualExecution $execution)
	{
		$execution->waitForSignal();
		$execution->getEngine()->pushCommand(new CreateSignalSubscriptionCommand($this->signal, $execution));
	}
}
