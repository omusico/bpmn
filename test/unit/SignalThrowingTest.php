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

class SignalThrowingTest extends BusinessProcessTestCase
{
	public function testIntermediateSignalThrow()
	{
		$this->deployFile('SignalThrowingTest.bpmn');
		
		$process = $this->runtimeService->startProcessInstanceByKey('SignalThrowingTest');
		$this->assertEquals(3, $this->runtimeService->createExecutionQuery()->count());
		$this->assertEquals(1, $this->taskService->createTaskQuery()->count());
		
		$task = $this->taskService->createTaskQuery()->findOne();
		$this->assertTrue($task instanceof TaskInterface);
		
		$this->taskService->complete($task->getId(), ['outcome' => 'OK :)']);
		
		$this->assertEquals(0, $this->runtimeService->createExecutionQuery()->count());
	}
}
