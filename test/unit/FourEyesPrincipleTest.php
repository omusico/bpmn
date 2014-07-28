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
use KoolKode\Process\Event\EnterNodeEvent;

class FourEyesPrincipleTest extends BusinessProcessTestCase
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
	public function testFourEyesPrinciple($a1, $a2, $result)
	{
		$this->deployFile('FourEyesPrincipleTest.bpmn');
		
		$this->registerMessageHandler('main', 'NotifyFirstApprover', function(MessageThrownEvent $event) {
			$this->runtimeService->startProcessInstanceByMessage(
				'FirstApprovalRequested',
				$event->execution->getBusinessKey()
			);
		});
		
		$this->registerMessageHandler('first', 'SendTask_3', function(MessageThrownEvent $event) use($a1) {
			$this->runtimeService->createMessageCorrelation('FirstDecisionReceived')
								 ->processBusinessKey($event->execution->getBusinessKey())
								 ->setVariable('approved', $a1 ? true : false)
								 ->correlate();
		});
		
		$this->registerMessageHandler('main', 'NotifySecondApprover', function(MessageThrownEvent $event) {
			$this->runtimeService->startProcessInstanceByMessage(
				'SecondApprovalRequested',
				$event->execution->getBusinessKey()
			);
		});
		
		$this->registerMessageHandler('second', 'SendTask_4', function(MessageThrownEvent $event) use($a2) {
			$this->runtimeService->createMessageCorrelation('SecondDecisionReceived')
								 ->processBusinessKey($event->execution->getBusinessKey())
								 ->setVariable('approved', $a2 ? true : false)
								 ->correlate();
		});
		
		$businessKey = 'Need decision!';
		
		$process = $this->runtimeService->startProcessInstanceByKey('main', $businessKey);
		$this->assertEquals(2, $this->runtimeService->createExecutionQuery()->count());
		
		$id = $process->getId();
		$lastNode = NULL;
		
		$this->eventDispatcher->connect(function(EnterNodeEvent $event) use($id, & $lastNode) {
			if($event->execution->getId() == $id) {
				$lastNode = $event->execution->getNode();
			}
		});
		
		$task = $this->taskService->createTaskQuery()->findOne();
		$this->taskService->complete($task->getId());
		
		if($a1)
		{
			$this->assertEquals(2, $this->runtimeService->createExecutionQuery()->count());
		
			$task = $this->taskService->createTaskQuery()->findOne();
			$this->taskService->complete($task->getId());
		}
		
		$this->assertEquals(0, $this->runtimeService->createExecutionQuery()->count());
		$this->assertEquals($result, $lastNode->getId());
	}
}
