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

/**
 * Helper for simplified message correlation to an execution.
 * 
 * @author Martin Schröder
 */
class MessageCorrelation
{
	use VariableQueryTrait;
	
	protected $messageName;
	
	protected $variables = [];
	
	protected $query;
	
	protected $runtimeService;
	
	public function __construct(RuntimeService $runtimeService, $messageName)
	{
		$this->runtimeService = $runtimeService;
		$this->messageName = (string)$messageName;
		
		$this->query = $runtimeService->createExecutionQuery()->messageEventSubscriptionName($this->messageName);
	}
	
	public function executionId($id)
	{
		$this->query->executionId($id);
		
		return $this;
	}
	
	public function processInstanceId($id)
	{
		$this->query->processInstanceId($id);
		
		return $this;
	}
	
	public function parentId($id)
	{
		$this->query->parentId($id);
		
		return $this;
	}
	
	public function activityId($id)
	{
		$this->query->activityId($id);
		
		return $this;
	}
	
	public function processBusinessKey($key)
	{
		$this->query->processBusinessKey($key);
		
		return $this;
	}
	
	public function processDefinitionKey($key)
	{
		$this->query->processDefinitionKey($key);
		
		return $this;
	}
	
	public function setVariable($name, $value)
	{
		$this->variables[(string)$name] = $value;
		
		return $this;
	}
	
	public function correlate()
	{
		$query = clone $this->query;
		
		foreach($this->variableValues as $var)
		{
			$query->variableValue($var);
		}
		
		return $this->runtimeService->messageEventReceived($this->messageName, $query->findOne()->getId(), $this->variables);
	}
}
