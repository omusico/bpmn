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

class NonInterruptingBoundaryEventTest extends BusinessProcessTestCase
{
	public function testWithoutSignal()
	{
		$this->deployFile('NonInterruptingBoundaryEventTest.bpmn');
		
		$process = $this->runtimeService->startProcessInstanceByKey('main');
		
		$task = $this->taskService->createTaskQuery()->findOne();
		$this->assertTrue($task instanceof TaskInterface);
		$this->assertEquals('dataTask', $task->getActivityId());
		$this->assertEquals(1, $this->runtimeService->createExecutionQuery()->count());
		$this->assertEquals(1, $this->runtimeService->createExecutionQuery()->signalEventSubscriptionName('TestSignal')->count());
		
		$this->taskService->complete($task->getId());
		$this->assertEquals(1, $this->runtimeService->createExecutionQuery()->count());
		$this->assertEquals(0, $this->runtimeService->createExecutionQuery()->signalEventSubscriptionName('TestSignal')->count());
		
		$task = $this->taskService->createTaskQuery()->findOne();
		$this->assertTrue($task instanceof TaskInterface);
		$this->assertEquals('submitTask', $task->getActivityId());
		
		$this->taskService->complete($task->getId());
		$this->assertEquals(0, $this->runtimeService->createExecutionQuery()->count());
	}
	
	public function testWithSignal()
	{
		$this->deployFile('NonInterruptingBoundaryEventTest.bpmn');
	
		$process = $this->runtimeService->startProcessInstanceByKey('main');
	
		$task = $this->taskService->createTaskQuery()->findOne();
		$this->assertTrue($task instanceof TaskInterface);
		$this->assertEquals('dataTask', $task->getActivityId());
		$this->assertEquals(1, $this->runtimeService->createExecutionQuery()->count());
		$this->assertEquals(1, $this->runtimeService->createExecutionQuery()->signalEventSubscriptionName('TestSignal')->count());
		$this->assertEquals(1, $this->taskService->createTaskQuery()->count());
		
		$this->runtimeService->signalEventReceived('TestSignal');
		$this->assertEquals(3, $this->runtimeService->createExecutionQuery()->count());
		$this->assertEquals(1, $this->runtimeService->createExecutionQuery()->signalEventSubscriptionName('TestSignal')->count());
		$this->assertEquals(2, $this->taskService->createTaskQuery()->count());
		
		$task = $this->taskService->createTaskQuery()->taskDefinitionKey('delayTask')->findOne();
		$this->assertTrue($task instanceof TaskInterface);
		$this->assertEquals('delayTask', $task->getActivityId());
		
		$this->taskService->complete($task->getId());
		$task = $this->taskService->createTaskQuery()->findOne();
		$this->assertTrue($task instanceof TaskInterface);
		$this->assertEquals('dataTask', $task->getActivityId());
		$this->assertEquals(2, $this->runtimeService->createExecutionQuery()->count());
		$this->assertEquals(1, $this->runtimeService->createExecutionQuery()->signalEventSubscriptionName('TestSignal')->count());
		$this->assertEquals(1, $this->taskService->createTaskQuery()->count());
		
		$this->taskService->complete($task->getId());
		$task = $this->taskService->createTaskQuery()->findOne();
		$this->assertTrue($task instanceof TaskInterface);
		$this->assertEquals('submitTask', $task->getActivityId());
		$this->assertEquals(1, $this->runtimeService->createExecutionQuery()->count());
		$this->assertEquals(0, $this->runtimeService->createExecutionQuery()->signalEventSubscriptionName('TestSignal')->count());
		$this->assertEquals(1, $this->taskService->createTaskQuery()->count());
		
		$this->taskService->complete($task->getId());
		$this->assertEquals(0, $this->runtimeService->createExecutionQuery()->count());
	}
}
