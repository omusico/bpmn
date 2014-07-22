<?php

/*
 * This file is part of KoolKode BPMN.
*
* (c) Martin Schröder <m.schroeder2007@gmail.com>
*
* For the full copyright and license information, please view the LICENSE
* file that was distributed with this source code.
*/

namespace KoolKode\BPMN\Behavior;

use KoolKode\Process\BehaviorInterface;
use KoolKode\Process\Execution;

class MessageStartEventBehavior implements BehaviorInterface
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
	
	public function execute(Execution $execution)
	{
		return $execution->takeAll(NULL, [$execution]);
	}
}
