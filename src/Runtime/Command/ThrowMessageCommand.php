<?php

/*
 * This file is part of KoolKode BPMN.
*
* (c) Martin Schröder <m.schroeder2007@gmail.com>
*
* For the full copyright and license information, please view the LICENSE
* file that was distributed with this source code.
*/

namespace KoolKode\BPMN\Runtime\Command;

use KoolKode\BPMN\Engine\AbstractBusinessCommand;
use KoolKode\BPMN\Engine\ProcessEngine;
use KoolKode\BPMN\Runtime\Event\MessageThrownEvent;
use KoolKode\Util\Uuid;

class ThrowMessageCommand extends AbstractBusinessCommand
{
	protected $executionId;
	
	protected $activityId;
	
	protected $payload;
	
	public function __construct(UUID $executionId, $activityId, array $payload = NULL)
	{
		$this->executionId = $executionId;
		$this->activityId = (string)$activityId;
		$this->payload = $payload;
	}
	
	public function getPriority()
	{
		return self::PRIORITY_DEFAULT - 500;
	}
	
	public function executeCommand(ProcessEngine $engine)
	{
		$execution = $engine->getRuntimeService()
							->createExecutionQuery()
							->executionId($this->executionId)
							->findOne();
		
		$engine->notify(new MessageThrownEvent(
			$execution,
			$this->activityId,
			$this->payload,
			$engine
		));
	}
}
