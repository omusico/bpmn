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

class ProcessInstance extends Execution implements ProcessInstanceInterface
{
	protected $businessKey;
	protected $processDefinitionId;
	
	public function __construct(UUID $id, UUID $processInstanceId, UUID $processDefinitionId, UUID $parentId = NULL, $activityId = NULL, $ended = false, $businessKey = NULL)
	{
		parent::__construct($id, $processInstanceId, $parentId, $activityId, $ended);
		
		$this->businessKey = ($businessKey === NULL) ? NULL : (string)$businessKey;
		$this->processDefinitionId = $processDefinitionId;
	}
	
	public function getBusinessKey()
	{
		return $this->businessKey;
	}
	
	public function getProcessDefinitionId()
	{
		return $this->processDefinitionId;
	}
}
