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

use KoolKode\Process\Node;
use KoolKode\Util\UUID;

/**
 * Base class for BPMN boundary events that can be attached to tasks and sub processes.
 * 
 * @author Martin Schröder
 */
abstract class AbstractBoundaryEventBehavior extends AbstractSignalableBehavior
{
	protected $attachedTo;
	
	protected $interrupting = true;
	
	public function __construct($attachedTo)
	{
		$this->attachedTo = (string)$attachedTo;
	}
	
	public function getAttachedTo()
	{
		return $this->attachedTo;
	}
	
	public function isInterrupting()
	{
		return $this->interrupting;
	}
	
	public function setInterrupting($interrupting)
	{
		$this->interrupting = $interrupting ? true : false;
	}
		
	/**
	 * Create an event subscription for the given execution.
	 * 
	 * @param VirtualExecution $execution
	 * @param string $activityId
	 * @param Node $node Start node that will be used after an event is triggered.
	 */
	public abstract function createEventSubscription(VirtualExecution $execution, $activityId, Node $node);
	
	public final function executeBehavior(VirtualExecution $execution)
	{
		throw new \RuntimeException(sprintf('Boundary events must not be executed directly'));
	}
	
	public function signalBehavior(VirtualExecution $execution, $signal, array $variables = [])
	{
		$model = $execution->getProcessModel();
		$event = $execution->getNode();
		$activity = $model->findNode($this->attachedTo);
		
		if($this->interrupting)
		{
			$behavior = $activity->getBehavior();
			
			if($behavior instanceof AbstractScopeBehavior)
			{
				$behavior->interruptBehavior($execution);
			}
			
			return $this->delegateSignalBehavior($execution, $signal, $variables);
		}
		
		if($execution->isConcurrent())
		{
			$root = $execution->getParentExecution();
		}
		else
		{
			$root = $execution->introduceConcurrentRoot();
		}
		
		$execution->getEngine()->syncExecutionState($root);
		$fork = $root->createExecution(true);
		
		$execution->setNode($activity);
		$execution->waitForSignal();
		
		$fork->setNode($event);
		
		$behavior = $activity->getBehavior();
		
		if($behavior instanceof AbstractScopeBehavior)
		{
			$behavior->createScopedEventSubscriptions($execution);
		}
		
		return $this->delegateSignalBehavior($fork, $signal, $variables);
	}
	
	protected function delegateSignalBehavior(VirtualExecution $execution, $signal, array $variables = [])
	{
		return parent::signalBehavior($execution, $signal, $variables);
	}
}
