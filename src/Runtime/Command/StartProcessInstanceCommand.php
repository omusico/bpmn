<?php

/*
 * This file is part of KoolKode BPMN.
*
* (c) Martin Schröder <m.schroeder2007@gmail.com>
*
* For the full copyright and license information, please view the LICENSE
* file that was distributed with this source code.
*/

namespace KoolKode\BPMN\Runtime\Command;

use KoolKode\BPMN\Engine\AbstractBusinessCommand;
use KoolKode\BPMN\Engine\ProcessEngine;
use KoolKode\BPMN\Engine\VirtualExecution;
use KoolKode\BPMN\Repository\BusinessProcessDefinition;
use KoolKode\BPMN\Runtime\Behavior\MessageStartEventBehavior;
use KoolKode\Util\Uuid;

class StartProcessInstanceCommand extends AbstractBusinessCommand
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
	
	public function executeCommand(ProcessEngine $engine)
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
		
		$process = new VirtualExecution(UUID::createRandom(), $engine, $definition);
		$process->setBusinessKey($this->businessKey);
			
		foreach($this->variables as $k => $v)
		{
			$process->setVariable($k, $v);
		}
		
		$engine->registerExecution($process);
		$process->execute(array_shift($initial));

		$activityId = ($process->getNode() === NULL) ? NULL : $process->getNode()->getId();
		
		return $process->getId();
	}
}
