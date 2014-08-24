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

use KoolKode\BPMN\Repository\BusinessProcessDefinition;
use KoolKode\BPMN\Test\BusinessProcessTestCase;

class MultiVersionDeploymentTest extends BusinessProcessTestCase
{
	public function testCanQueryForAllVersions()
	{
		$this->deployFile('MultiVersionDeploymentTest.bpmn');
		$this->deployFile('MultiVersionDeploymentTest.bpmn');
		
		$query = $this->repositoryService->createProcessDefinitionQuery();
		$query->processDefinitionKey('multi1');
		$this->assertEquals(2, $query->count());
	}
	
	public function testCanQueryForLatesVersion()
	{
		$this->deployFile('MultiVersionDeploymentTest.bpmn');
		$this->deployFile('MultiVersionDeploymentTest.bpmn');
	
		$query = $this->repositoryService->createProcessDefinitionQuery();
		$query->processDefinitionKey('multi1');
		$query->latestVersion();
		$this->assertEquals(1, $query->count());
		
		$result = $query->findAll();
		$def = array_pop($result);
		$this->assertTrue($def instanceof BusinessProcessDefinition);
		$this->assertEquals('multi1', $def->getKey());
		$this->assertEquals(2, $def->getRevision());
	}
	
	public function testCanQueryForSpecificVersion()
	{
		$this->deployFile('MultiVersionDeploymentTest.bpmn');
		$this->deployFile('MultiVersionDeploymentTest.bpmn');
	
		$query = $this->repositoryService->createProcessDefinitionQuery();
		$query->processDefinitionKey('multi1');
		$query->processDefinitionVersion(1);
		$this->assertEquals(1, $query->count());
		
		$result = $query->findAll();
		$def = array_pop($result);
		$this->assertTrue($def instanceof BusinessProcessDefinition);
		$this->assertEquals('multi1', $def->getKey());
		$this->assertEquals(1, $def->getRevision());
	}
}
