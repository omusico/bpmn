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
use KoolKode\BPMN\Test\BusinessProcessTestCase;
use KoolKode\BPMN\Test\MessageHandler;
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
		
		$this->firstApproval = $a1 ? true : false;
		$this->secondApproval = $a2 ? true : false;
		
		$businessKey = 'Need decision!';
		
		$process = $this->runtimeService->startProcessInstanceByKey('main', $businessKey);
		$this->assertEquals(2, $this->runtimeService->createExecutionQuery()->count());
		$this->assertEquals(1, $this->runtimeService->createExecutionQuery()->messageEventSubscriptionName('FirstDecisionReceived')->count());
		
		$id = $process->getId();
		$lastNode = NULL;
		
		$this->eventDispatcher->connect(function(EnterNodeEvent $event) use($id, & $lastNode) {
			if($event->execution->getId() == $id) {
				$lastNode = $event->execution->getNode();
			}
		});
		
		$task = $this->taskService->createTaskQuery()->findOne();
		$this->taskService->complete($task->getId());
		$this->assertEquals(0, $this->runtimeService->createExecutionQuery()->messageEventSubscriptionName('FirstDecisionReceived')->count());
		
		if($a1)
		{
			$this->assertEquals(2, $this->runtimeService->createExecutionQuery()->count());
			$this->assertEquals(1, $this->runtimeService->createExecutionQuery()->messageEventSubscriptionName('SecondDecisionReceived')->count());
		
			$task = $this->taskService->createTaskQuery()->findOne();
			$this->taskService->complete($task->getId());
		}
		else
		{
			$this->assertEquals(0, $this->runtimeService->createExecutionQuery()->messageEventSubscriptionName('SecondDecisionReceived')->count());
		}
		
		$this->assertEquals(0, $this->runtimeService->createExecutionQuery()->count());
		$this->assertEquals($result, $lastNode->getId());
	}
	
	/**
	 * @MessageHandler("NotifyFirstApprover", processKey = "main")
	 * 
	 * @param MessageThrownEvent $event
	 */
	public function notifyFirstApprover(MessageThrownEvent $event)
	{
		$this->runtimeService->startProcessInstanceByMessage(
			'FirstApprovalRequested',
			$event->execution->getBusinessKey()
		);
	}
	
	protected $firstApproval = false;
	
	/**
	 * @MessageHandler("SendTask_3", processKey = "first")
	 *
	 * @param MessageThrownEvent $event
	 */
	public function decideFirstApproval(MessageThrownEvent $event)
	{
		$this->runtimeService->createMessageCorrelation('FirstDecisionReceived')
							 ->processBusinessKey($event->execution->getBusinessKey())
							 ->setVariable('approved', $this->firstApproval)
							 ->correlate();
	}
	
	/**
	 * @MessageHandler("NotifySecondApprover", processKey = "main")
	 * 
	 * @param MessageThrownEvent $event
	 */
	public function notifySecondApprover(MessageThrownEvent $event)
	{
		$this->runtimeService->startProcessInstanceByMessage(
			'SecondApprovalRequested',
			$event->execution->getBusinessKey()
		);
	}
	
	protected $secondApproval = false;
	
	/**
	 * @MessageHandler("SendTask_4", processKey = "second")
	 * 
	 * @param MessageThrownEvent $event
	 */
	public function decideSecondApproval(MessageThrownEvent $event)
	{
		$this->runtimeService->createMessageCorrelation('SecondDecisionReceived')
							 ->processBusinessKey($event->execution->getBusinessKey())
							 ->setVariable('approved', $this->secondApproval)
							 ->correlate();
	}
}
