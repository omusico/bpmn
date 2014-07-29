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

use KoolKode\BPMN\Engine\VirtualExecution;
use KoolKode\Process\Behavior\SignalableBehaviorInterface;
use KoolKode\Process\Node;

/**
 * Contract for BPMN intermediate catch events that can be used with an event based gateway.
 * 
 * @author Martin Schröder
 */
interface IntermediateCatchEventInterface extends SignalableBehaviorInterface
{
	/**
	 * Create an event subscription for the given execution.
	 * 
	 * @param VirtualExecution $execution
	 * @param Node $node Start node that will be used after an event is triggered.
	 */
	public function createEventSubscription(VirtualExecution $execution, Node $node = NULL);
}
