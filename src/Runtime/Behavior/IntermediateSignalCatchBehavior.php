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
use KoolKode\Process\Node;

/**
 * Subscribes to a signal event and waits for the signals arrival.
 * 
 * @author Martin Schröder
 */
class IntermediateSignalCatchBehavior extends AbstractSignalableBehavior implements IntermediateCatchEventInterface
{
	protected $signal;
	
	public function __construct($signal)
	{
		$this->signal = (string)$signal;
	}
	
	public function executeBehavior(VirtualExecution $execution)
	{
		$this->createEventSubscription($execution);
		
		$execution->waitForSignal();
	}
	
	public function createEventSubscription(VirtualExecution $execution, Node $node = NULL)
	{
		$execution->getEngine()->pushCommand(new CreateSignalSubscriptionCommand(
			$this->signal,
			$execution,
			($node === NULL) ? $execution->getNode() : $node
		));
	}
}
