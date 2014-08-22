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
use KoolKode\Process\Event\EnterNodeEvent;

class FourEyesRevisitedTest extends BusinessProcessTestCase
{
	public function provideApprovalDecisions()
	{
		return [
			[false, false, 'RequestRejected1'],
			[false, true, 'RequestRejected1'],
			[true, false, 'RequestRejected2'],
			[true, true, 'RequestApproved']
		];
	}
	
	/**
	 * @dataProvider provideApprovalDecisions
	 */
	public function testFourRevisited($a1, $a2, $result)
	{
		$this->deployFile('FourEyesRevisitedTest.bpmn');
		
		$process = $this->runtimeService->startProcessInstanceByKey('main');
		$this->assertEquals(2, $this->runtimeService->createExecutionQuery()->count());
		
		$id = $process->getId();
		$lastNode = NULL;
		
		$this->eventDispatcher->connect(function(EnterNodeEvent $event) use($id, & $lastNode) {
			if($event->execution->getId() == $id) {
				$lastNode = $event->execution->getNode();
			}
		});
		
		$task = $this->taskService->createTaskQuery()->findOne();
		$this->assertTrue($task instanceof TaskInterface);
		$this->assertEquals('user(A)', $task->getAssignee());
		
		$this->taskService->complete($task->getId(), [
			'approved' => $a1 ? true : false
		]);
		
		if($a1)
		{
			$this->assertEquals(2, $this->runtimeService->createExecutionQuery()->count());
		
			$task = $this->taskService->createTaskQuery()->findOne();
			$this->assertTrue($task instanceof TaskInterface);
			$this->assertEquals('user(B)', $task->getAssignee());
			
			$this->taskService->complete($task->getId(), [
				'approved' => $a2 ? true : false
			]);
		}
		
		$this->assertEquals(0, $this->runtimeService->createExecutionQuery()->count());
		$this->assertEquals($result, $lastNode->getId());
	}
}
