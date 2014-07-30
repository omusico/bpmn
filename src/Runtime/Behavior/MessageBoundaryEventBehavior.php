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

use KoolKode\BPMN\Engine\AbstractBoundaryEventBehavior;
use KoolKode\BPMN\Engine\VirtualExecution;
use KoolKode\BPMN\Runtime\Command\CreateMessageSubscriptionCommand;
use KoolKode\Process\Node;

class MessageBoundaryEventBehavior extends AbstractBoundaryEventBehavior
{
	protected $message;
	
	public function __construct($attachedTo, $message)
	{
		parent::__construct($attachedTo);
		
		$this->message = (string)$message;
	}
	
	public function createEventSubscription(VirtualExecution $execution, Node $node)
	{
		$execution->getEngine()->pushCommand(new CreateMessageSubscriptionCommand(
			$this->message,
			$execution,
			$node
		));
	}
}
