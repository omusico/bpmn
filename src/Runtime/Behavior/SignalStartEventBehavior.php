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
use KoolKode\BPMN\Engine\VirtualExecution;

/**
 * Similar to basic start event, signal subscriptions are handled by repository services.
 * 
 * @author Martin Schröder
 */
class SignalStartEventBehavior extends AbstractBehavior
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
}
