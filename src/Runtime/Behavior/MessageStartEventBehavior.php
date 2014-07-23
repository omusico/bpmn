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

class MessageStartEventBehavior extends AbstractBehavior
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
}
