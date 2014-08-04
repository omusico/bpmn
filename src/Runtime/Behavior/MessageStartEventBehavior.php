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
class MessageStartEventBehavior extends AbstractBehavior implements EventSubscriptionBehaviorInterface
{
	protected $messageName;
	
	public function __construct($messageName)
	{
		$this->messageName = (string)$messageName;
	}
	
	public function getMessageName()
	{
		return $this->messageName;
	}
	
	public function createEventSubscription(VirtualExecution $execution, Node $node = NULL)
	{
		$execution->getEngine()->pushCommand(new CreateMessageSubscriptionCommand(
			$this->messageName,
			$execution,
			($node === NULL) ? $execution->getNode() : $node
		));
	}
}
