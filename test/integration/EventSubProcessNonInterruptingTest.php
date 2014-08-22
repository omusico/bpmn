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
use KoolKode\BPMN\Test\BusinessProcessTestCase;

class EventSubProcessNonInterruptingTest extends BusinessProcessTestCase
{
	public function testProcessWithoutMessage()
	{
		$this->deployFile('EventSubProcessNonInterruptingTest.bpmn');
		
		$process = $this->runtimeService->startProcessInstanceByKey('main');
		$this->assertEquals(2, $this->runtimeService->createExecutionQuery()->count());
		
		$task = $this->taskService->createTaskQuery()->findOne();
		$this->assertTrue($task instanceof TaskInterface);
		$this->assertEquals('UserTask_2', $task->getActivityId());
		
		$this->taskService->complete($task->getId());
		$task = $this->taskService->createTaskQuery()->findOne();
		$this->assertTrue($task instanceof TaskInterface);
		$this->assertEquals('UserTask_3', $task->getActivityId());
		$this->assertEquals(1, $this->runtimeService->createExecutionQuery()->count());
		
		$this->taskService->complete($task->getId());
		$this->assertEquals(0, $this->runtimeService->createExecutionQuery()->count());
	}
	
	public function testProcessWithMessage()
	{
		$this->deployFile('EventSubProcessNonInterruptingTest.bpmn');
	
		$process = $this->runtimeService->startProcessInstanceByKey('main');
		$this->assertEquals(2, $this->runtimeService->createExecutionQuery()->count());
	
		$task = $this->taskService->createTaskQuery()->findOne();
		$this->assertTrue($task instanceof TaskInterface);
		$this->assertEquals('UserTask_2', $task->getActivityId());
		$this->assertEquals(1, $this->taskService->createTaskQuery()->count());
		
		$this->runtimeService->messageEventReceived('OrderItemAddedMessage', $process->getId());
	
		$task = $this->taskService->createTaskQuery()->taskDefinitionKey('UserTask_1')->findOne();
		$this->assertTrue($task instanceof TaskInterface);
		$this->assertEquals('UserTask_1', $task->getActivityId());
		$this->assertEquals(4, $this->runtimeService->createExecutionQuery()->count());
		$this->assertEquals(2, $this->taskService->createTaskQuery()->count());
		
		$this->taskService->complete($task->getId());
		$task = $this->taskService->createTaskQuery()->findOne();
		$this->assertTrue($task instanceof TaskInterface);
		$this->assertEquals('UserTask_2', $task->getActivityId());
		$this->assertEquals(1, $this->taskService->createTaskQuery()->count());
		
		$this->taskService->complete($task->getId());
		$task = $this->taskService->createTaskQuery()->findOne();
		$this->assertTrue($task instanceof TaskInterface);
		$this->assertEquals('UserTask_3', $task->getActivityId());
		$this->assertEquals(1, $this->runtimeService->createExecutionQuery()->count());
		$this->assertEquals(1, $this->taskService->createTaskQuery()->count());
	
		$this->taskService->complete($task->getId());
		$this->assertEquals(0, $this->runtimeService->createExecutionQuery()->count());
	}
}
