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

use KoolKode\BPMN\Engine\AbstractScopeBehavior;
use KoolKode\BPMN\Engine\VirtualExecution;

/**
 * Executes an embedded sub process within a child execution with shared variable scope.
 * 
 * @author Martin Schröder
 */
class SubProcessBehavior extends AbstractScopeBehavior
{
	protected $startNodeId;
	
	public function __construct($startNodeId)
	{
		$this->startNodeId = (string)$startNodeId;
	}
	
	public function executeBehavior(VirtualExecution $execution)
	{
		$definition = $execution->getProcessDefinition();
		
		$execution->getEngine()->debug('Starting sub process "{process}"', [
			'process' => $this->getStringValue($this->name, $execution->getExpressionContext())
		]);
		
		$startNode = $definition->findNode($this->startNodeId);
		
		if(!$startNode->getBehavior() instanceof NoneStartEventBehavior)
		{
			throw new \RuntimeException(sprintf(
				'Cannot start sub process %s ("%s") because it is missing start node %s',
				$execution->getNode()->getId(),
				$this->getStringValue($this->name, $execution->getExpressionContext()),
				$this->startNodeId
			));
		}
		
		$execution->waitForSignal();
		
		$sub = $execution->createNestedExecution($definition, false);
		$sub->execute($startNode);
	}
	
	public function signalBehavior(VirtualExecution $execution, $signal, array $variables = [])
	{
		$sub = $variables[VirtualExecution::KEY_EXECUTION];
		
		if(!$sub instanceof VirtualExecution)
		{
			throw new \RuntimeException(sprintf('Missing nested execution being signaled'));
		}
		
		$execution->getEngine()->debug('Resuming {execution} after sub process "{process}"', [
			'execution' => (string)$execution,
			'process' => $this->getStringValue($this->name, $execution->getExpressionContext())
		]);
		
		return $execution->takeAll(NULL, [$execution]);
	}
	
	public function interruptBehavior(VirtualExecution $execution)
	{
		foreach($execution->findChildExecutions() as $sub)
		{
			$sub->terminate(false);
		}
		
		return parent::interruptBehavior($execution);
	}
}
