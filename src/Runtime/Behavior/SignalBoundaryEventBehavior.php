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
use KoolKode\BPMN\Runtime\Command\CreateSignalSubscriptionCommand;
use KoolKode\Process\Node;

/**
 * Signal catch event bound to an event scope.
 * 
 * @author Martin Schröder
 */
class SignalBoundaryEventBehavior extends AbstractBoundaryEventBehavior
{
	protected $signal;
	
	public function __construct($attachedTo, $signal)
	{
		parent::__construct($attachedTo);
		
		$this->signal = (string)$signal;
	}
	
	public function createEventSubscription(VirtualExecution $execution, $activityId, Node $node)
	{
		$execution->getEngine()->executeCommand(new CreateSignalSubscriptionCommand(
			$this->signal,
			$execution,
			$activityId,
			$node
		));
	}
}
