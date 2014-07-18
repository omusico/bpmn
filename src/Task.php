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

class Task implements TaskInterface
{
	protected $id;
	protected $executionId;
	protected $name;
	protected $activityId;
	protected $created;
	protected $claimDate;
	protected $assignee;
	
	public function __construct(UUID $id, UUID $executionId, $name, $activityId, \DateTime $created, \DateTime $claimDate = NULL, $assignee = NULL)
	{
		$this->id = $id;
		$this->executionId = $executionId;
		$this->name = (string)$name;
		$this->activityId = (string)$activityId;
		$this->created = clone $created;
		$this->claimDate = ($claimDate === NULL) ? NULL : clone $claimDate;
		$this->assignee = ($assignee === NULL) ? NULL : (string)$assignee;
	}
	
	public function getId()
	{
		return $this->id;
	}
	
	public function getExecutionId()
	{
		return $this->executionId;
	}
	
	public function getName()
	{
		return $this->name;
	}
	
	public function getActivityId()
	{
		return $this->activityId;
	}
	
	public function getCreated()
	{
		return clone $this->created;
	}
	
	public function getClaimDate()
	{
		return ($this->claimDate === NULL) ? NULL : clone $this->claimDate;
	}
	
	public function getAssignee()
	{
		return $this->assignee;
	}
}
