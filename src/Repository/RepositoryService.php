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
use KoolKode\BPMN\Repository\Command\CreateDeploymentCommand;
use KoolKode\BPMN\Repository\Command\DeployBusinessProcessCommand;

class RepositoryService
{
	protected $engine;
	
	public function __construct(ProcessEngine $engine)
	{
		$this->engine = $engine;
	}
	
	public function createDeploymentQuery()
	{
		return new DeploymentQuery($this->engine);
	}
	
	public function createProcessDefinitionQuery()
	{
		return new ProcessDefinitionQuery($this->engine);
	}
	
	public function createDeployment($name)
	{
		return new DeploymentBuilder($name);
	}
	
	public function deployProcess(\SplFileInfo $file, $name = NULL)
	{
		$builder = new DeploymentBuilder(($name === NULL) ? $file->getFilename() : $name);
		$builder->addExtensions($file->getExtension());
		$builder->addResource($file->getFilename(), $file);
		
		return $this->deploy($builder);
	}
	
	public function deploy(DeploymentBuilder $builder)
	{
		$id = $this->engine->executeCommand(new CreateDeploymentCommand($builder));
		
		return $this->createDeploymentQuery()->deploymentId($id)->findOne();
	}
	
	public function deployProcessBuilder(BusinessProcessBuilder $builder)
	{
		return $this->engine->executeCommand(new DeployBusinessProcessCommand($builder));
	}
}
