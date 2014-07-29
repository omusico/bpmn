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

class SignalBoundaryEventTest extends BusinessProcessTestCase
{
	public function testWithoutSignal()
	{
		$this->deployFile('SignalBoundaryEventTest.bpmn');
		
		$process = $this->runtimeService->startProcessInstanceByKey('main');
		
		$task = $this->taskService->createTaskQuery()->findOne();
		$this->assertTrue($task instanceof TaskInterface);
		$this->assertEquals(1, $this->runtimeService->createExecutionQuery()->count());
		$this->assertEquals(1, $this->runtimeService->createExecutionQuery()->signalEventSubscriptionName('TimeoutSignal')->count());
		
		$this->taskService->complete($task->getId());
		$this->assertEquals(1, $this->runtimeService->createExecutionQuery()->count());
		$this->assertEquals(0, $this->runtimeService->createExecutionQuery()->signalEventSubscriptionName('TimeoutSignal')->count());
	}
	
	public function testWithSignal()
	{
		$this->deployFile('SignalBoundaryEventTest.bpmn');
	
		$process = $this->runtimeService->startProcessInstanceByKey('main');
	
		$task = $this->taskService->createTaskQuery()->findOne();
		$this->assertTrue($task instanceof TaskInterface);
		$this->assertEquals('dataTask', $task->getActivityId());
		$this->assertEquals(1, $this->runtimeService->createExecutionQuery()->count());
		$this->assertEquals(1, $this->runtimeService->createExecutionQuery()->signalEventSubscriptionName('TimeoutSignal')->count());
	
		$this->runtimeService->signalEventReceived('TimeoutSignal');
		$this->assertEquals(1, $this->taskService->createTaskQuery()->count());
		
		$task = $this->taskService->createTaskQuery()->findOne();
		$this->assertTrue($task instanceof TaskInterface);
		$this->assertEquals('delayTask', $task->getActivityId());
		
		$this->taskService->complete($task->getId());
		$this->assertEquals(0, $this->runtimeService->createExecutionQuery()->count());
	}
}
