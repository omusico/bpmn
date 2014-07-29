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

/**
 * Base class for all BPMN node behaviors.
 * 
 * @author Martin Schröder
 */
abstract class AbstractBehavior implements BehaviorInterface
{
	/**
	 * {@inheritdoc}
	 */
	public function execute(Execution $execution)
	{
		return $this->executeBehavior($execution);
	}
	
	/**
	 * Execute the behavior in the context of the given execution.
	 * 
	 * @param VirtualExecution $execution
	 */
	public function executeBehavior(VirtualExecution $execution)
	{
		return $execution->takeAll(NULL, [$execution]);
	}
}
