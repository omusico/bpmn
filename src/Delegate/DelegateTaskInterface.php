<?php

/*
 * This file is part of KoolKode BPMN.
*
* (c) Martin Schröder <m.schroeder2007@gmail.com>
*
* For the full copyright and license information, please view the LICENSE
* file that was distributed with this source code.
*/

namespace KoolKode\BPMN\Delegate;

/**
 * Contract for a custom task implementation to be used with a BPMN service task.
 * 
 * @author Martin Schröder
 */
interface DelegateTaskInterface
{
	/**
	 * Execute business logic related to the given execution.
	 * 
	 * @param DelegateExecutionInterface $execution
	 */
	public function execute(DelegateExecutionInterface $execution);
}
