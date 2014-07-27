<?php

/*
 * This file is part of KoolKode BPMN.
*
* (c) Martin Schröder <m.schroeder2007@gmail.com>
*
* For the full copyright and license information, please view the LICENSE
* file that was distributed with this source code.
*/

namespace KoolKode\BPMN\Runtime\Event;

use KoolKode\BPMN\Engine\ProcessEngine;
use KoolKode\BPMN\Runtime\ExecutionInterface;
use KoolKode\Util\Uuid;

class MessageThrownEvent
{
	public $execution;
	
	public $engine;
	
	public function __construct(ExecutionInterface $execution, ProcessEngine $engine)
	{
		$this->execution = $execution;
		$this->engine = $engine;
	}
}
