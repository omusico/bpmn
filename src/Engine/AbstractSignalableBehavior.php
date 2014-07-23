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

use KoolKode\Process\Execution;
use KoolKode\Process\Behavior\SignalableBehaviorInterface;

abstract class AbstractSignalableBehavior extends AbstractBehavior implements SignalableBehaviorInterface
{
	protected function executeBehavior(VirtualExecution $execution)
	{
		$execution->waitForSignal();
	}
		
	public final function signal(Execution $execution, $signal, array $variables = [])
	{
		return $this->signalBehavior($execution, $signal, $variables);
	}
	
	protected function signalBehavior(VirtualExecution $execution, $signal, array $variables = [])
	{
		foreach($variables as $k => $v)
		{
			$execution->setVariable($k, $v);
		}
				
		return $execution->takeAll(NULL, [$execution]);
	}
}
