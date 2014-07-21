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
	public $processInstanceId;
	
	public $activityId;
	
	public $variables;
	
	public $engine;
	
	public function __construct(UUID $processInstanceId, $activityId, array $variables, ProcessEngine $engine)
	{
		$this->processInstanceId = $processInstanceId;
		$this->activityId = (string)$activityId;
		$this->variables = $variables;
		$this->engine = $engine;
	}
}
