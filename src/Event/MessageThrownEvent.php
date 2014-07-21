<?php

/*
 * This file is part of KoolKode BPMN.
*
* (c) Martin Schröder <m.schroeder2007@gmail.com>
*
* For the full copyright and license information, please view the LICENSE
* file that was distributed with this source code.
*/

namespace KoolKode\BPMN\Event;

use KoolKode\BPMN\ProcessEngine;
use KoolKode\Util\Uuid;

class MessageThrownEvent
{
	protected $processInstanceId;
	protected $activityId;
	protected $variables;
	
	protected $engine;
	
	public function __construct(UUID $processInstanceId, $activityId, array $variables, ProcessEngine $engine)
	{
		$this->processInstanceId = $processInstanceId;
		$this->activityId = (string)$activityId;
		$this->variables = $variables;
		$this->engine = $engine;
	}
	
	public function getProcessInstanceId()
	{
		return $this->processInstanceId;
	}
	
	public function getActivityId()
	{
		return $this->activityId;
	}
	
	public function getVariables()
	{
		return $this->variables;
	}
	
	public function getProcessEngine()
	{
		return $this->engine;
	}
}
