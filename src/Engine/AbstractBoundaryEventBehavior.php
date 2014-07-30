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

/**
 * Base class for BPMN boundary events that can be attached to tasks and sub processes.
 * 
 * @author Martin Schröder
 */
abstract class AbstractBoundaryEventBehavior extends AbstractSignalableBehavior
{
	protected $attachedTo;
	
	public function __construct($attachedTo)
	{
		$this->attachedTo = (string)$attachedTo;
	}
	
	public function getAttachedTo()
	{
		return $this->attachedTo;
	}
		
	/**
	 * Create an event subscription for the given execution.
	 * 
	 * @param VirtualExecution $execution
	 * @param Node $node Start node that will be used after an event is triggered.
	 */
	public abstract function createEventSubscription(VirtualExecution $execution, Node $node);
	
	public final function executeBehavior(VirtualExecution $execution)
	{
		throw new \RuntimeException(sprintf('Boundary events must not be executed directly'));
	}
	
	public function signalBehavior(VirtualExecution $execution, $signal, array $variables = [])
	{
		$definition = $execution->getProcessDefinition();
		$activity = $definition->findNode($this->attachedTo);
		
		$activity->getBehavior()->interruptBehavior($execution);
		
		return parent::signalBehavior($execution, $signal, $variables);
	}
}
