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

use KoolKode\BPMN\Runtime\Behavior\EventSubProcessBehavior;
use KoolKode\BPMN\Runtime\Command\ClearEventSubscriptionsCommand;
use KoolKode\Process\Execution;
use KoolKode\Process\Node;

/**
 * Base class for all BPMN elements that have scope semantics, that is boundary events
 * may be attached to them.
 * 
 * @author Martin Schröder
 */
abstract class AbstractScopeBehavior extends AbstractSignalableBehavior
{	
	/**
	 * Will clear all boundary event subscriptions after the signal has been dispatched.
	 * 
	 * @param Execution $execution
	 * @param string $signal
	 * @param array<string, mixed> $variables
	 */
	public function signal(Execution $execution, $signal, array $variables = [])
	{	
		try
		{
			$this->signalBehavior($execution, $signal, $variables);
		}
		finally
		{
			$execution->getEngine()->executeCommand(new ClearEventSubscriptionsCommand(
				$execution,
				$execution->getNode()->getId()
			));
		}
	}
	
	/**
	 * Needs to be implemented whenever additional actions are needed in order to cancel an activity.
	 * 
	 * @param VirtualExecution $execution
	 */
	public function interruptBehavior(VirtualExecution $execution) { }
	
	/**
	 * Have boundary events subscribe to events.
	 * 
	 * @param Execution $execution
	 */
	public function createScopedEventSubscriptions(VirtualExecution $execution)
	{
		$activityId = $execution->getNode()->getId();
		
		foreach($this->findAttachedBoundaryEvents($execution) as $event)
		{
			$behavior = $event->getBehavior();
			
			if($behavior instanceof AbstractBoundaryEventBehavior)
			{
				$behavior->createEventSubscription($execution, $activityId, $event);
			}
		}
	}
	
	/**
	 * Collect all boundary events connected to the activity / node of the given execution.
	 * 
	 * @param VirtualExecution $execution
	 * @return array<Node>
	 */
	public function findAttachedBoundaryEvents(VirtualExecution $execution)
	{
		$model = $execution->getProcessModel();
		$ref = ($execution->getNode() === NULL) ? NULL : $execution->getNode()->getId();
		$events = [];
	
		foreach($model->findNodes() as $node)
		{
			$behavior = $node->getBehavior();
				
			if($behavior instanceof AbstractBoundaryEventBehavior)
			{
				if($ref == $behavior->getAttachedTo())
				{
					$events[] = $node;
				}
			}
		}
	
		return $events;
	}
}
