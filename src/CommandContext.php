<?php

/*
 * This file is part of KoolKode BPMN.
*
* (c) Martin Schröder <m.schroeder2007@gmail.com>
*
* For the full copyright and license information, please view the LICENSE
* file that was distributed with this source code.
*/

namespace KoolKode\BPMN;

use KoolKode\Process\Execution;
use KoolKode\Util\UUID;

class CommandContext
{
	protected $engine;
	
	public function __construct(ProcessEngine $engine)
	{
		$this->engine = $engine;
	}
	
	public function getProcessEngine()
	{
		return $this->engine;
	}
	
	public function prepareQuery($sql)
	{
		return $this->engine->prepareQuery($sql);
	}
	
	public function loadExecution($execution)
	{
		if($execution instanceof Execution)
		{
			return $execution;
		}
		
		if(is_array($execution))
		{
			return $this->engine->unserializeExecution($execution);
		}
		
		return $this->engine->findExecution(new UUID($execution));
	}
	
	public function executeCommand(CommandInterface $command)
	{
		return $this->engine->executeCommand($command);
	}
	
	public function pushCommand(CommandInterface $command)
	{
		$this->engine->pushCommand($command);
	}
	
	public function notify($event)
	{
		return $this->engine->getInternalEngine()->notify($event);
	}
}
