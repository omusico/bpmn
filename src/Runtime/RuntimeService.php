<?php

/*
 * This file is part of KoolKode BPMN.
*
* (c) Martin Schröder <m.schroeder2007@gmail.com>
*
* For the full copyright and license information, please view the LICENSE
* file that was distributed with this source code.
*/

namespace KoolKode\BPMN\Runtime;

use KoolKode\BPMN\Engine\ProcessEngine;
use KoolKode\BPMN\Runtime\Command\MessageEventReceivedCommand;
use KoolKode\BPMN\Runtime\Command\SetProcessVariableCommand;
use KoolKode\BPMN\Runtime\Command\SignalEventReceivedCommand;
use KoolKode\BPMN\Runtime\Command\StartProcessInstanceCommand;
use KoolKode\Util\Uuid;

class RuntimeService
{
	protected $engine;
	
	public function __construct(ProcessEngine $engine)
	{
		$this->engine = $engine;
	}
	
	public function createProcessInstanceQuery()
	{
		return new ExecutionQuery($this->engine, true);
	}
	
	public function createExecutionQuery()
	{
		return new ExecutionQuery($this->engine);
	}
	
	public function createMessageCorrelation($messageName)
	{
		return new MessageCorrelation($this, $messageName);
	}
	
	public function messageEventReceived($messageName, UUID $executionId, array $variables = [])
	{
		$this->engine->executeCommand(new MessageEventReceivedCommand($messageName, $executionId, $variables));
	}
	
	public function signalEventReceived($signalName, UUID $executionId = NULL, array $variables = [])
	{
		$this->engine->executeCommand(new SignalEventReceivedCommand($signalName, $executionId, $variables));
	}
	
	public function startProcessInstanceByKey($processDefinitionKey, $businessKey = NULL, array $variables = [])
	{
		$query = $this->engine->getRepositoryService()->createProcessDefinitionQuery();
		$def = $query->processDefinitionKey($processDefinitionKey)->latestVersion()->findOne();
		$startNode = $def->findNoneStartEvent();
		
		$id = $this->engine->executeCommand(new StartProcessInstanceCommand($def, $startNode, $businessKey, $variables));
		
		return $this->createProcessInstanceQuery()->processInstanceId($id)->findOne();
	}
	
	public function startProcessInstanceByMessage($messageName, $businessKey = NULL, array $variables = [])
	{
		$query = $this->engine->getRepositoryService()->createProcessDefinitionQuery();
		$def = $query->messageEventSubscriptionName($messageName)->latestVersion()->findOne();
		$startNode = $def->findMessageStartEvent($messageName);
		
		$id = $this->engine->executeCommand(new StartProcessInstanceCommand($def, $startNode, $businessKey, $variables));
		
		return $this->createProcessInstanceQuery()->processInstanceId($id)->findOne();
	}
	
	public function setProcessVariable(UUID $executionId, $variableName, $variableValue)
	{
		$this->engine->executeCommand(new SetProcessVariableCommand($executionId, $variableName, $variableValue));
	}
}
