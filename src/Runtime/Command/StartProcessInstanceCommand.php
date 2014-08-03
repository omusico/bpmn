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
use KoolKode\Process\Command\ExecuteNodeCommand;
use KoolKode\Process\Node;
use KoolKode\Util\Uuid;

class StartProcessInstanceCommand extends AbstractBusinessCommand
{
	const START_TYPE_MESSAGE = 'message';
	const START_TYPE_SIGNAL = 'signal';
	
	protected $definition;
	
	protected $startNode;
	
	protected $businessKey;
	
	protected $variables;
	
	public function __construct(BusinessProcessDefinition $definition, Node $startNode, $businessKey = NULL, array $variables = [])
	{
		$this->definition = $definition;
		$this->startNode = $startNode->getId();
		$this->businessKey = ($businessKey === NULL) ? NULL : (string)$businessKey;
		$this->variables = $variables;
	}
	
	public function executeCommand(ProcessEngine $engine)
	{
		$definition = $this->definition->getModel();

		$process = new VirtualExecution(UUID::createRandom(), $engine, $definition);
		$process->setBusinessKey($this->businessKey);
		
		foreach($this->variables as $k => $v)
		{
			$process->setVariable($k, $v);
		}
		
		$engine->registerExecution($process);
		
		$engine->pushDeferredCommand(new ExecuteNodeCommand($process, $definition->findNode($this->startNode)));
		
		return $process->getId();
	}
}
