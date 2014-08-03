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

class SubProcessTest extends BusinessProcessTestCase
{
	public function testWithoutSignal()
	{
		$this->deployFile('SubProcessTest.bpmn');
		
		$process = $this->runtimeService->startProcessInstanceByKey('main');
		
		$task = $this->taskService->createTaskQuery()->findOne();
		$this->assertTrue($task instanceof TaskInterface);
		$this->assertEquals('Task A', $task->getName());
		$this->assertEquals(1, $this->taskService->createTaskQuery()->count());
		$this->assertEquals(2, $this->runtimeService->createExecutionQuery()->count());
		
		$this->taskService->complete($task->getId());
		$task = $this->taskService->createTaskQuery()->findOne();
		$this->assertTrue($task instanceof TaskInterface);
		$this->assertEquals('Task B', $task->getName());
		$this->assertEquals(1, $this->taskService->createTaskQuery()->count());
		$this->assertEquals(2, $this->runtimeService->createExecutionQuery()->count());
		
		$this->taskService->complete($task->getId());
		$task = $this->taskService->createTaskQuery()->findOne();
		$this->assertTrue($task instanceof TaskInterface);
		$this->assertEquals('Task C', $task->getName());
		$this->assertEquals(1, $this->taskService->createTaskQuery()->count());
		$this->assertEquals(1, $this->runtimeService->createExecutionQuery()->count());
		
		$this->taskService->complete($task->getId());
		$this->assertEquals(0, $this->runtimeService->createExecutionQuery()->count());
	}
	
	public function testWithSignal()
	{
		$this->deployFile('SubProcessTest.bpmn');
	
		$process = $this->runtimeService->startProcessInstanceByKey('main');
	
		$task = $this->taskService->createTaskQuery()->findOne();
		$this->assertTrue($task instanceof TaskInterface);
		$this->assertEquals('Task A', $task->getName());
		$this->assertEquals(1, $this->taskService->createTaskQuery()->count());
		$this->assertEquals(2, $this->runtimeService->createExecutionQuery()->count());
	
		$this->taskService->complete($task->getId());
		$task = $this->taskService->createTaskQuery()->findOne();
		$this->assertTrue($task instanceof TaskInterface);
		$this->assertEquals('Task B', $task->getName());
		$this->assertEquals(1, $this->taskService->createTaskQuery()->count());
		$this->assertEquals(2, $this->runtimeService->createExecutionQuery()->count());
		
		$this->runtimeService->signalEventReceived('InterruptSignal');
		$task = $this->taskService->createTaskQuery()->findOne();
		$this->assertTrue($task instanceof TaskInterface);
		$this->assertEquals('Task D', $task->getName());
		$this->assertEquals(1, $this->taskService->createTaskQuery()->count());
		$this->assertEquals(1, $this->runtimeService->createExecutionQuery()->count());
	
		$this->taskService->complete($task->getId());
		$task = $this->taskService->createTaskQuery()->findOne();
		$this->assertTrue($task instanceof TaskInterface);
		$this->assertEquals('Task A', $task->getName());
		$this->assertEquals(1, $this->taskService->createTaskQuery()->count());
		$this->assertEquals(2, $this->runtimeService->createExecutionQuery()->count());
		
		$this->taskService->complete($task->getId());
		$task = $this->taskService->createTaskQuery()->findOne();
		$this->assertTrue($task instanceof TaskInterface);
		$this->assertEquals('Task B', $task->getName());
		$this->assertEquals(1, $this->taskService->createTaskQuery()->count());
		$this->assertEquals(2, $this->runtimeService->createExecutionQuery()->count());
		
		$this->taskService->complete($task->getId());
		$task = $this->taskService->createTaskQuery()->findOne();
		$this->assertTrue($task instanceof TaskInterface);
		$this->assertEquals('Task C', $task->getName());
		$this->assertEquals(1, $this->taskService->createTaskQuery()->count());
		$this->assertEquals(1, $this->runtimeService->createExecutionQuery()->count());
		
		$this->taskService->complete($task->getId());
		$this->assertEquals(0, $this->runtimeService->createExecutionQuery()->count());
	}
}
