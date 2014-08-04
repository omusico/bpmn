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
use KoolKode\BPMN\Runtime\Command\CreateSignalSubscriptionCommand;
use KoolKode\Process\Node;

/**
 * Similar to basic start event, signal subscriptions are handled by repository services.
 * 
 * @author Martin Schröder
 */
class SignalStartEventBehavior extends AbstractBehavior implements EventSubscriptionBehaviorInterface
{
	protected $signalName;
	
	public function __construct($signalName)
	{
		$this->signalName = (string)$signalName;
	}
	
	public function getSignalName()
	{
		return $this->signalName;
	}
	
	public function createEventSubscription(VirtualExecution $execution, Node $node = NULL)
	{
		$execution->getEngine()->pushCommand(new CreateSignalSubscriptionCommand(
			$this->signalName,
			$execution,
			($node === NULL) ? $execution->getNode() : $node
		));
	}
}
