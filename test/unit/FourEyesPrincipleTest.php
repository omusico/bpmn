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

class FourEyesPrincipleTest extends BusinessProcessTestCase
{
	public function testFourEyesPrinciple()
	{
		$this->deployFile('FourEyesPrincipleTest.bpmn');
		
		$this->delegateTasks->registerTask(new DetermineDecisionMakersTask());
		
		$this->registerMessageHandler('main', 'NotifyFirstApprover', function(MessageThrownEvent $event) {
			$this->runtimeService->startProcessInstanceByMessage(
				'FirstApprovalRequested',
				$event->execution->getBusinessKey()
			);
		});
		
		$this->registerMessageHandler('first', 'SendTask_3', function(MessageThrownEvent $event) {
			$this->runtimeService->createMessageCorrelation('FirstDecisionReceived')
								 ->processBusinessKey($event->execution->getBusinessKey())
								 ->setVariable('approved', true)
								 ->correlate();
		});
		
		$this->registerMessageHandler('main', 'NotifySecondApprover', function(MessageThrownEvent $event) {
			$this->runtimeService->startProcessInstanceByMessage(
				'SecondApprovalRequested',
				$event->execution->getBusinessKey()
			);
		});
		
		$this->registerMessageHandler('second', 'SendTask_4', function(MessageThrownEvent $event) {
			$this->runtimeService->createMessageCorrelation('SecondDecisionReceived')
								 ->processBusinessKey($event->execution->getBusinessKey())
								 ->setVariable('approved', true)
								 ->correlate();
		});
		
		$businessKey = 'Need decision!';
		
		$process = $this->runtimeService->startProcessInstanceByKey('main', $businessKey);
		$this->assertEquals(2, $this->runtimeService->createExecutionQuery()->count());
		
		$task = $this->taskService->createTaskQuery()->findOne();
		$this->taskService->complete($task->getId());
		$this->assertEquals(2, $this->runtimeService->createExecutionQuery()->count());
		
		$task = $this->taskService->createTaskQuery()->findOne();
		$this->taskService->complete($task->getId());
		$this->assertEquals(0, $this->runtimeService->createExecutionQuery()->count());
	}
}
