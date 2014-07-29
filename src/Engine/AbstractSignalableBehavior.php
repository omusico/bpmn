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

use KoolKode\Process\Behavior\SignalableBehaviorInterface;
use KoolKode\Process\Execution;

abstract class AbstractSignalableBehavior extends AbstractBehavior implements SignalableBehaviorInterface
{	
	public function executeBehavior(VirtualExecution $execution)
	{
		$execution->waitForSignal();
	}
		
	public function signal(Execution $execution, $signal, array $variables = [])
	{
		return $this->signalBehavior($execution, $signal, $variables);
	}
	
	public function signalBehavior(VirtualExecution $execution, $signal, array $variables = [])
	{		
		foreach($variables as $k => $v)
		{
			$execution->setVariable($k, $v);
		}
				
		return $execution->takeAll(NULL, [$execution]);
	}
}
