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

use KoolKode\BPMN\Engine\BasicAttributesTrait;
use KoolKode\Process\Behavior\InclusiveChoiceBehavior;

/**
 * Chooses any number of outgoing sequence flows that have conditions evaluating to true.
 * 
 * @author Martin Schröder
 */
class InclusiveGatewayBehavior extends InclusiveChoiceBehavior
{
	use BasicAttributesTrait;
	
	public function setDefaultFlow($flow = NULL)
	{
		$this->defaultTransition = ($flow === NULL) ? NULL : (string)$flow;
	}
}
