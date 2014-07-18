<?php

/*
 * This file is part of KoolKode BPMN.
*
* (c) Martin Schröder <m.schroeder2007@gmail.com>
*
* For the full copyright and license information, please view the LICENSE
* file that was distributed with this source code.
*/

namespace KoolKode\BPMN\Command;

use KoolKode\BPMN\Behavior\MessageStartEventBehavior;
use KoolKode\BPMN\BusinessProcessDefinition;
use KoolKode\BPMN\CommandContext;
use KoolKode\BPMN\InternalProcessInstance;
use KoolKode\BPMN\ProcessInstance;
use KoolKode\Util\Uuid;

class StartProcessInstanceCommand extends AbstractCommand
{
	const START_TYPE_MESSAGE = 'message';
	const START_TYPE_SIGNAL = 'signal';
	
	protected $definition;
	
	protected $startNode;
	
	protected $businessKey;
	
	protected $variables;
	
	public function __construct(BusinessProcessDefinition $definition, array $startNode = NULL, $businessKey = NULL, array $variables = [])
	{
		$this->definition = $definition;
		$this->startNode = $startNode;
		$this->businessKey = ($businessKey === NULL) ? NULL : (string)$businessKey;
		$this->variables = $variables;
	}
	
	public function execute(CommandContext $context)
	{
		$definition = $this->definition->getModel();
		$initial = [];
		
		if($this->startNode === NULL)
		{
			$initial = $definition->findInitialNodes();
			
			if(count($initial) != 1)
			{
				throw new \RuntimeException(sprintf('Process "%s" does not declare a non-start event', $this->definition->getKey()));
			}
		}
		else
		{
			foreach($this->startNode as $key => $value)
			{
				switch($key)
				{
					case self::START_TYPE_MESSAGE:
						foreach($definition->findNodes() as $node)
						{
							$behavior = $node->getBehavior();
						
							if($behavior instanceof MessageStartEventBehavior)
							{
								if($behavior->getMessageName() == $value)
								{
									$initial = [$node];
						
									break 2;
								}
							}
						}
						break;
					default:
						throw new \OutOfBoundsException(sprintf('No such start event found: "%s" in process "%s"', $key, $this->definition->getKey()));
				}
				
				break;
			}
			
			if(count($initial) != 1)
			{
				throw new \RuntimeException(sprintf('Cannot use more than 1 start event in process "%s"', $this->definition->getKey()));
			}
		}
		
		$processEngine = $context->getProcessEngine();
		
		$process = new InternalProcessInstance(UUID::createRandom(), $processEngine, $definition, $this->businessKey);
		$process->execute(array_shift($initial));
			
		foreach($this->variables as $k => $v)
		{
			$process->setVariable($k, $v);
		}
		
		$processEngine->registerExecution($process);

		$engine = $processEngine->getInternalEngine();
	
		while($engine->executeNextCommand());
		
		$nid = ($process->getNode() === NULL) ? NULL : $process->getNode()->getId();
		
		return new ProcessInstance($process->getId(), $process->getId(), $this->definition->getId(), NULL, $nid, $process->isTerminated(), $this->businessKey);
	}
}
