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

use KoolKode\Process\ProcessDefinition;
use KoolKode\Util\Uuid;

class InternalProcessInstance extends InternalExecution
{
	public function __construct(UUID $id, ProcessEngine $processEngine, ProcessDefinition $processDefinition, $businessKey = NULL)
	{
		$this->id = $id;
		$this->engine = $processEngine->getInternalEngine();
		$this->processEngine = $processEngine;
		$this->processDefinition = $processDefinition;
		$this->state = self::STATE_SCOPE | self::STATE_ACTIVE;
		$this->businessKey = ($businessKey === NULL) ? NULL : (string)$businessKey;
	}
	
	public function __toString()
	{
		return sprintf('process(%s)', $this->id);
	}
}
