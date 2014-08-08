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

use KoolKode\BPMN\Engine\AbstractBoundaryEventBehavior;
use KoolKode\BPMN\Engine\EventSubscriptionBehaviorInterface;
use KoolKode\BPMN\Engine\VirtualExecution;
use KoolKode\Process\Node;

/**
 * Executes an embedded sub process within a child execution with shared variable scope.
 * 
 * @author Martin Schröder
 */
class EventSubProcessBehavior extends AbstractBoundaryEventBehavior
{
	protected $startNodeId;
	
	public function __construct($attachedTo, $startNodeId)
	{
		parent::__construct($attachedTo);
		
		$this->startNodeId = (string)$startNodeId;
	}
	
	public function createEventSubscription(VirtualExecution $execution, $activityId, Node $node)
	{
		$startNode = $execution->getProcessDefinition()->findNode($this->startNodeId);
		$behavior = $startNode->getBehavior();
		
		if(!$behavior instanceof EventSubscriptionBehaviorInterface)
		{
			throw new \InvalidArgumentException(sprintf('Start node %s cannot subscribe to event', $startNode->getId()));
		}
		
		$behavior->createEventSubscription($execution, $activityId, $node);
	}
	
	// FIXME: Variable scopes of executions are not correct, need to fix this using correct execution hiererchy.
	// As of now boundary events are always executed outside of the scope, executing them inside the scope
	// needs special handling in signalBehavior() of AbstractBoundaryEventBehavior.
	
	protected function delegateSignalBehavior(VirtualExecution $execution, $signal, array $variables = [])
	{
		$definition = $execution->getProcessDefinition();
		$event = $execution->getNode();
		$activity = $definition->findNode($this->attachedTo);
		
		$execution->setNode($activity);
		$execution->setTransition(NULL);
		$execution->waitForSignal();
		
		if($this->interrupting)
		{
			$sub = $execution->createNestedExecution($definition);
		}
		else
		{
			$sub = $execution;
		}
		
		foreach($variables as $k => $v)
		{
			$sub->setVariable($k, $v);
		}
		
		return $sub->execute($definition->findNode($this->startNodeId));
	}
}
