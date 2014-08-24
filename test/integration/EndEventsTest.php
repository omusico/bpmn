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

use KoolKode\BPMN\Runtime\Event\MessageThrownEvent;
use KoolKode\BPMN\Task\TaskInterface;
use KoolKode\BPMN\Test\BusinessProcessTestCase;
use KoolKode\BPMN\Test\MessageHandler;

class EndEventsTest extends BusinessProcessTestCase
{
	public function testSignalAndMessageEndAndStartEvents()
	{
		$this->deployFile('EndEventsTest.bpmn');
		
		$process = $this->runtimeService->startProcessInstanceByKey('test1');
		
		$task = $this->taskService->createTaskQuery()->findOne();
		$this->assertTrue($task instanceof TaskInterface);
		$this->assertEquals('Task A', $task->getName());
		$this->assertNull($task->getAssignee());
		$this->assertNull($task->getClaimDate());
		$this->assertEquals(1, $this->runtimeService->createExecutionQuery()->count());
		
		$this->taskService->claim($task->getId(), 'foobar');
		$task = $this->taskService->createTaskQuery()->findOne();
		$this->assertTrue($task instanceof TaskInterface);
		$this->assertEquals('Task A', $task->getName());
		$this->assertEquals('foobar', $task->getAssignee());
		$this->assertTrue($task->getClaimDate() instanceof \DateTimeImmutable);
		
		$this->taskService->unclaim($task->getId());
		$task = $this->taskService->createTaskQuery()->findOne();
		$this->assertTrue($task instanceof TaskInterface);
		$this->assertEquals('Task A', $task->getName());
		$this->assertNull($task->getAssignee());
		$this->assertNull($task->getClaimDate());
		
		$this->taskService->complete($task->getId());
		$this->assertEquals(2, $this->runtimeService->createExecutionQuery()->count());
		$this->assertEquals(2, $this->taskService->createTaskQuery()->count());
		
		$task = $this->taskService->createTaskQuery()->taskDefinitionKey('taskB')->findOne();
		$this->assertTrue($task instanceof TaskInterface);
		$this->assertEquals('Task B', $task->getName());
		
		$this->taskService->complete($task->getId());
		$this->assertEquals(1, $this->runtimeService->createExecutionQuery()->count());
		$this->assertEquals(1, $this->taskService->createTaskQuery()->count());
		
		$task = $this->taskService->createTaskQuery()->taskDefinitionKey('taskC')->findOne();
		$this->assertTrue($task instanceof TaskInterface);
		$this->assertEquals('Task C', $task->getName());
		
		$this->taskService->complete($task->getId());
		$this->assertEquals(0, $this->runtimeService->createExecutionQuery()->count());
	}
	
	/**
	 * @MessageHandler("messageEndEvent1", processKey = "test2")
	 * 
	 * @param MessageThrownEvent $event
	 */
	public function verifyMessageEndEvent(MessageThrownEvent $event)
	{
		$this->assertEquals('messageEndEvent1', $event->execution->getActivityId());
	}
}
