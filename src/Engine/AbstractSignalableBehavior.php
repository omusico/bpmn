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

/**
 * Base class for all BPMN node behaviors that need signal interaction.
 * 
 * @author Martin Schröder
 */
abstract class AbstractSignalableBehavior extends AbstractBehavior implements SignalableBehaviorInterface
{
	/**
	 * Base implementation will simply enter a wait state in the given execution.
	 * 
	 * @param VirtualExecution $execution
	 */
	public function executeBehavior(VirtualExecution $execution)
	{
		$execution->waitForSignal();
	}

	/**
	 * {@inheritdoc}
	 */
	public function signal(Execution $execution, $signal, array $variables = [])
	{
		return $this->signalBehavior($execution, $signal, $variables);
	}
	
	/**
	 * Signal behavior, the default implementation will set the given variables in the
	 * given execution and take all outgoing transitions afterwards.
	 * 
	 * @param VirtualExecution $execution
	 * @param string $signal
	 * @param array<string, mixed> $variables
	 */
	public function signalBehavior(VirtualExecution $execution, $signal, array $variables = [])
	{		
		foreach($variables as $k => $v)
		{
			$execution->setVariable($k, $v);
		}
				
		return $execution->takeAll(NULL, [$execution]);
	}
}
