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
use KoolKode\Process\Behavior\SyncBehavior;

/**
 * Provides join and fork behavior within BPMN processes.
 * 
 * @author Martin Schröder
 */
class ParallelGatewayBehavior extends SyncBehavior
{
	use BasicAttributesTrait;
}
