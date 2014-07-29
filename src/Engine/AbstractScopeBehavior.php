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

use KoolKode\BPMN\Runtime\Command\ClearEventSubscriptionsCommand;
use KoolKode\Process\Execution;

abstract class AbstractScopeBehavior extends AbstractSignalableBehavior
{	
	public function execute(Execution $execution)
	{
		$this->setupBoundaryEvents($execution);
		
		return parent::execute($execution);
	}
		
	public function signal(Execution $execution, $signal, array $variables = [])
	{
		$execution->getEngine()->executeCommand(new ClearEventSubscriptionsCommand($execution));
			
		return $this->signalBehavior($execution, $signal, $variables);
	}
	
	public function interruptBehavior(VirtualExecution $execution) { }
	
	protected function setupBoundaryEvents(VirtualExecution $execution)
	{
		foreach($this->findAttachedBoundaryEvents($execution) as $event)
		{
			$event->getBehavior()->createEventSubscription($execution, $event);
		}
	}
	
	protected function findAttachedBoundaryEvents(VirtualExecution $execution)
	{
		$definition = $execution->getProcessDefinition();
		$ref = ($execution->getNode() === NULL) ? NULL : $execution->getNode()->getId();
		$events = [];
	
		foreach($definition->findNodes() as $node)
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
