<?php

/*
 * This file is part of KoolKode BPMN.
 *
 * (c) Martin Schröder <m.schroeder2007@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace KoolKode\BPMN\Engine;

use KoolKode\Process\Behavior\BehaviorInterface;
use KoolKode\Process\Node;

/**
 * Contract for all behaviors that can subscribe to an event.
 * 
 * @author Martin Schröder
 */
interface EventSubscriptionBehaviorInterface extends BehaviorInterface
{
	/**
	 * Create an event subscription for the given execution directed to the target node
	 * or the current node of the execution.
	 *
	 * @param VirtualExecution $execution
	 * @param Node $node Context node that will be used after an event is triggered.
	 */
	public function createEventSubscription(VirtualExecution $execution, Node $node = NULL);
}
