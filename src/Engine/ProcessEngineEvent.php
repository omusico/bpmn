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

/**
 * Base class for all process engine events.
 * 
 * @author Martin Schröder
 */
abstract class ProcessEngineEvent
{
	/**
	 * Provides access to the process engine.
	 *
	 * @var ProcessEngineInterface
	 */
	public $engine;
}
