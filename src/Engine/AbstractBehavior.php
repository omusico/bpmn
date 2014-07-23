<?php

/*
 * This file is part of KoolKode BPMN.
 *
 * (c) Martin Schröder <m.schroeder2007@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace KoolKode\BPMN\Engine;

use KoolKode\Process\Behavior\BehaviorInterface;
use KoolKode\Process\Execution;

abstract class AbstractBehavior implements BehaviorInterface
{
	public final function execute(Execution $execution)
	{
		return $this->executeBehavior($execution);
	}
	
	protected function executeBehavior(VirtualExecution $execution)
	{
		return $execution->takeAll(NULL, [$execution]);
	}
}
