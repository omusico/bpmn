<?php

/*
 * This file is part of KoolKode BPMN.
*
* (c) Martin Schröder <m.schroeder2007@gmail.com>
*
* For the full copyright and license information, please view the LICENSE
* file that was distributed with this source code.
*/

namespace KoolKode\BPMN;

use KoolKode\Util\UUID;

class Execution implements ExecutionInterface
{
	protected $id;
	protected $parentId;
	protected $processInstanceId;
	protected $activityId;
	protected $ended;
	
	public function __construct(UUID $id, UUID $processInstanceId, UUID $parentId = NULL, $activityId = NULL, $ended = false)
	{
		$this->id = $id;
		$this->parentId = $parentId;
		$this->processInstanceId = $processInstanceId;
		$this->activityId = (string)$activityId;
		$this->ended = $ended ? true : false;
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
	
	public function getActivityId()
	{
		return $this->activityId;
	}
	
	public function isEnded()
	{
		return $this->ended;
	}
}
