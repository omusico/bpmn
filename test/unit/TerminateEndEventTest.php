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

use KoolKode\BPMN\Task\TaskInterface;

class TerminateEndEventTest extends BusinessProcessTestCase
{
	public function testTerminateBranch()
	{
		$this->deployFile('TerminateEndEventTest.bpmn');
		
		$process = $this->runtimeService->startProcessInstanceByKey('main');
		$this->assertEquals(3, $this->runtimeService->createExecutionQuery()->count());
		$this->assertEquals(2, $this->taskService->createTaskQuery()->count());
		
		$task = $this->taskService->createTaskQuery()->taskDefinitionKey('taskA')->findOne();
		$this->assertTrue($task instanceof TaskInterface);
		$this->assertEquals('Task A', $task->getName());
		
		$this->taskService->complete($task->getId());
		$this->assertEquals(0, $this->runtimeService->createExecutionQuery()->count());
	}
	
	public function testNonTerminatingBranch()
	{
		$this->deployFile('TerminateEndEventTest.bpmn');
	
		$process = $this->runtimeService->startProcessInstanceByKey('main');
		$this->assertEquals(3, $this->runtimeService->createExecutionQuery()->count());
		$this->assertEquals(2, $this->taskService->createTaskQuery()->count());
	
		$task = $this->taskService->createTaskQuery()->taskDefinitionKey('taskB')->findOne();
		$this->assertTrue($task instanceof TaskInterface);
		$this->assertEquals('Task B', $task->getName());
	
		$this->taskService->complete($task->getId());
		$this->assertEquals(2, $this->runtimeService->createExecutionQuery()->count());
		
		$task = $this->taskService->createTaskQuery()->taskDefinitionKey('taskA')->findOne();
		$this->assertTrue($task instanceof TaskInterface);
		$this->assertEquals('Task A', $task->getName());
		
		$this->taskService->complete($task->getId());
		$this->assertEquals(0, $this->runtimeService->createExecutionQuery()->count());
	}
}
