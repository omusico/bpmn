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

use KoolKode\Process\Command\AbstractCommand;
use KoolKode\Process\EngineInterface;

/**
 * Base class for a BPMN engine command.
 * 
 * @author Martin Schröder
 */
abstract class AbstractBusinessCommand extends AbstractCommand
{
	/**
	 * {@inheritdoc}
	 */
	public final function execute(EngineInterface $engine)
	{
		return $this->executeCommand($engine);
	}
	
	/**
	 * Execute the command logic using the given BPMN process engine.
	 * 
	 * @param ProcessEngine $engine
	 */
	protected abstract function executeCommand(ProcessEngine $engine);
}
