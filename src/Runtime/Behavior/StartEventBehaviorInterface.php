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

use KoolKode\Process\Behavior\BehaviorInterface;

/**
 * Contract for all start event behaviors.
 * 
 * @author Martin Schröder
 */
interface StartEventBehaviorInterface extends BehaviorInterface
{
	/**
	 * Check if this start event is used to start a sub process.
	 * 
	 * @return boolean
	 */
	public function isSubProcessStart();
	
	/**
	 * Check if the start event is interrupting (only related to sub process).
	 * 
	 * @return boolean
	 */
	public function isInterrupting();
}
