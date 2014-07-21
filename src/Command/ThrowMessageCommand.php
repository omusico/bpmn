<?php

/*
 * This file is part of KoolKode BPMN.
*
* (c) Martin Schröder <m.schroeder2007@gmail.com>
*
* For the full copyright and license information, please view the LICENSE
* file that was distributed with this source code.
*/

namespace KoolKode\BPMN\Command;

use KoolKode\BPMN\CommandContext;
use KoolKode\BPMN\Event\MessageThrowEvent;
use KoolKode\Util\Uuid;

class ThrowMessageCommand extends AbstractCommand
{
	protected $processInstanceId;
	
	protected $activityId;
	
	protected $processVariables;
	
	public function __construct(UUID $procesInstanceId, $activityId, array $processVariables = NULL)
	{
		$this->processInstanceId = $procesInstanceId;
		$this->activityId = (string)$activityId;
		$this->processVariables = $processVariables;
	}
	
	public function getPriority()
	{
		return self::PRIORITY_DEFAULT - 500;
	}
	
	public function execute(CommandContext $context)
	{
		// TODO: Integrate with domain events (queue events in dispatcher and trigger them when a transaction is commited successfully)
		
		$event = new MessageThrowEvent($this->processInstanceId, $this->activityId, $this->processVariables, $context->getProcessEngine());
		
		$context->notify($event);
	}
}
