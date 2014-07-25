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

use KoolKode\Util\UUID;

class MessageCorrelation
{
	protected $messageName;
	
	protected $processInstanceId;
	
	protected $businessKey;
	
	protected $variables = [];
	
	protected $runtimeService;
	
	public function __construct(RuntimeService $runtimeService, $messageName)
	{
		$this->runtimeService = $runtimeService;
		$this->messageName = (string)$messageName;
	}
	
	public function processInstanceId($pid)
	{
		$this->processInstanceId = new UUID($pid);
		
		return $this;
	}
	
	public function processBusinessKey($businessKey)
	{
		$this->businessKey = (string)$businessKey;
		
		return $this;
	}
	
	public function setVariable($name, $value)
	{
		$this->variables[(string)$name] = $value;
		
		return $this;
	}
	
	public function correlate()
	{
		$query = $this->runtimeService->createExecutionQuery();
		$query->messageEventSubscriptionName($this->messageName);
		
		if($this->processInstanceId !== NULL)
		{
			$query->processInstanceId($this->processInstanceId);
		}
		
		if($this->businessKey !== NULL)
		{
			$query->processBusinessKey($this->businessKey);
		}
		
		$this->runtimeService->messageEventReceived($this->messageName, $query->findOne()->getId(), $this->variables);
	}
}
