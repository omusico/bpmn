<?php

/*
 * This file is part of KoolKode BPMN.
*
* (c) Martin Schröder <m.schroeder2007@gmail.com>
*
* For the full copyright and license information, please view the LICENSE
* file that was distributed with this source code.
*/

namespace KoolKode\BPMN\Runtime\Behavior;

use KoolKode\BPMN\Engine\AbstractBehavior;
use KoolKode\BPMN\Engine\EventSubscriptionBehaviorInterface;
use KoolKode\BPMN\Engine\VirtualExecution;
use KoolKode\BPMN\Runtime\Command\CreateMessageSubscriptionCommand;
use KoolKode\Process\Node;

/**
 * Similar to basic start event, message subscriptions are handled by repository services.
 * 
 * @author Martin Schröder
 */
class MessageStartEventBehavior extends AbstractBehavior implements StartEventBehaviorInterface, EventSubscriptionBehaviorInterface
{
	protected $messageName;
	
	protected $subProcessStart;
	
	protected $interrupting = true;
	
	public function __construct($messageName, $subProcessStart = false)
	{
		$this->messageName = (string)$messageName;
		$this->subProcessStart = $subProcessStart ? true : false;
	}
	
	public function getMessageName()
	{
		return $this->messageName;
	}
	
	public function isSubProcessStart()
	{
		return $this->subProcessStart;
	}
	
	public function isInterrupting()
	{
		return $this->interrupting;
	}
	
	public function setInterrupting($interrupting)
	{
		$this->interrupting = $interrupting ? true : false;
	}
	
	public function createEventSubscription(VirtualExecution $execution, $activityId, Node $node = NULL)
	{
		$execution->getEngine()->executeCommand(new CreateMessageSubscriptionCommand(
			$this->messageName,
			$execution,
			$activityId,
			($node === NULL) ? $execution->getNode() : $node
		));
	}
}
