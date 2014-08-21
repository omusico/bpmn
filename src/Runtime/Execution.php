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

use KoolKode\BPMN\Repository\BusinessProcessDefinition;
use KoolKode\Util\UUID;

class Execution implements ExecutionInterface, \JsonSerializable
{
	protected $id;
	protected $parentId;
	protected $processInstanceId;
	protected $definition;
	protected $activityId;
	protected $ended;
	protected $businessKey;
	
	public function __construct(BusinessProcessDefinition $definition, UUID $id, UUID $processInstanceId, UUID $parentId = NULL, $activityId = NULL, $ended = false, $businessKey = NULL)
	{
		$this->definition = $definition;
		$this->id = $id;
		$this->parentId = $parentId;
		$this->processInstanceId = $processInstanceId;
		$this->activityId = (string)$activityId;
		$this->ended = $ended ? true : false;
		$this->businessKey = ($businessKey === NULL) ? NULL : (string)$businessKey; 
	}
	
	public function jsonSerialize()
	{
		return [
			'id' => (string)$this->id,
			'parentId' => ($this->parentId === NULL) ? NULL : (string)$this->parentId,
			'processInstanceId' => ($this->processInstanceId === NULL) ? NULL : (string)$this->processInstanceId,
			'processDefinitionId' => (string)$this->definition->getId(),
			'processDefinitionKey' => $this->definition->getKey(),
			'processDefiitionRevision' => $this->definition->getRevision(),
			'activityId' => $this->activityId,
			'businessKey' => $this->businessKey
		];
	}
	
	public function isProcessInstance()
	{
		return $this->id == $this->processInstanceId;
	}
	
	public function getId()
	{
		return $this->id;
	}
	
	public function getParentId()
	{
		return $this->parentId;
	}
	
	public function getProcessInstanceId()
	{
		return $this->processInstanceId;
	}
	
	public function getProcessDefinition()
	{
		return $this->definition;
	}
	
	public function getActivityId()
	{
		return $this->activityId;
	}
	
	public function isEnded()
	{
		return $this->ended;
	}
	
	public function getBusinessKey()
	{
		return $this->businessKey;
	}
}
