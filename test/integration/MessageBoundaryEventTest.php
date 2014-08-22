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

class MessageBoundaryEventTest extends BusinessProcessTestCase
{
	public function testWithoutMessage()
	{
		$this->deployFile('MessageBoundaryEventTest.bpmn');
		
		$process = $this->runtimeService->startProcessInstanceByKey('main');
		
		$task = $this->taskService->createTaskQuery()->findOne();
		$this->assertTrue($task instanceof TaskInterface);
		$this->assertEquals('dataTask', $task->getActivityId());
		$this->assertEquals('This is just a dummy task.', $task->getDocumentation());
		$this->assertEquals(1, $this->runtimeService->createExecutionQuery()->count());
		$this->assertEquals(1, $this->runtimeService->createExecutionQuery()->messageEventSubscriptionName('TimeoutMessage')->count());
		
		$this->taskService->complete($task->getId());
		$this->assertEquals(1, $this->runtimeService->createExecutionQuery()->count());
		$this->assertEquals(0, $this->runtimeService->createExecutionQuery()->messageEventSubscriptionName('TimeoutMessage')->count());
		
		$task = $this->taskService->createTaskQuery()->findOne();
		$this->assertTrue($task instanceof TaskInterface);
		$this->assertEquals('submitTask', $task->getActivityId());
		$this->assertEquals('', $task->getDocumentation());
		
		$this->taskService->complete($task->getId());
		$this->assertEquals(0, $this->runtimeService->createExecutionQuery()->count());
	}
	
	public function testWithMessage()
	{
		$this->deployFile('MessageBoundaryEventTest.bpmn');
	
		$process = $this->runtimeService->startProcessInstanceByKey('main');
	
		$task = $this->taskService->createTaskQuery()->findOne();
		$this->assertTrue($task instanceof TaskInterface);
		$this->assertEquals('dataTask', $task->getActivityId());
		$this->assertEquals(1, $this->runtimeService->createExecutionQuery()->count());
		$this->assertEquals(1, $this->runtimeService->createExecutionQuery()->messageEventSubscriptionName('TimeoutMessage')->count());
	
		$this->runtimeService->messageEventReceived('TimeoutMessage', $process->getId());
		$this->assertEquals(1, $this->runtimeService->createExecutionQuery()->count());
		$this->assertEquals(0, $this->runtimeService->createExecutionQuery()->messageEventSubscriptionName('TimeoutMessage')->count());
		$this->assertEquals(1, $this->taskService->createTaskQuery()->count());
		
		$task = $this->taskService->createTaskQuery()->findOne();
		$this->assertTrue($task instanceof TaskInterface);
		$this->assertEquals('delayTask', $task->getActivityId());
		
		$this->taskService->complete($task->getId());
		$this->assertEquals(0, $this->runtimeService->createExecutionQuery()->count());
	}
}
