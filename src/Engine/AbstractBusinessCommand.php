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

abstract class AbstractBusinessCommand extends AbstractCommand
{
	public final function execute(EngineInterface $engine)
	{
		return $this->executeCommand($engine);
	}
	
	protected abstract function executeCommand(ProcessEngine $engine);
}
