<?php

/*
 * This file is part of KoolKode BPMN.
*
* (c) Martin Schröder <m.schroeder2007@gmail.com>
*
* For the full copyright and license information, please view the LICENSE
* file that was distributed with this source code.
*/

namespace KoolKode\BPMN\Repository;

use KoolKode\BPMN\BusinessProcessBuilder;
use KoolKode\BPMN\DiagramLoader;
use KoolKode\BPMN\Engine\ProcessEngine;
use KoolKode\BPMN\Repository\Command\DeployBusinessProcessCommand;

class RepositoryService
{
	protected $engine;
	
	public function __construct(ProcessEngine $engine)
	{
		$this->engine = $engine;
	}
	
	public function createProcessDefinitionQuery()
	{
		return new ProcessDefinitionQuery($this->engine);
	}
	
	public function deployDiagram($file)
	{
		$loader = new DiagramLoader();
		$defs = [];
		
		foreach((array)$loader->parseDiagramFile($file) as $builder)
		{
			$defs = $this->deployBusinessProcess($builder);
		}
		
		return $defs;
	}
	
	public function deployBusinessProcess(BusinessProcessBuilder $builder)
	{
		return $this->engine->executeCommand(new DeployBusinessProcessCommand($builder));
	}
}
