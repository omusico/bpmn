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

class PizzaCollaborationTest extends BusinessProcessTestCase
{
	public function testPizzaProcess()
	{
		$this->deployFile('PizzaCollaboration.bpmn');
		
		$businessKey = 'Pizza Funghi';
		
		$this->eventDispatcher->connect(function(MessageThrownEvent $event) use($businessKey) {
			
			switch($event->activityId)
			{
				case 'sendPizzaOrder':
					$this->runtimeService->startProcessInstanceByMessage('pizzaOrderReceived', $businessKey, [
						'csustomerProcessId' => $event->execution->getProcessInstanceId()
					]);
					break;
				case 'deliverPizza':
					$id = $event->variables['csustomerProcessId'];
					$target = $this->runtimeService->createExecutionQuery()
								   ->processInstanceId($id)
								   ->messageEventSubscriptionName('pizzaReceived')
								   ->findOne();
					
					$this->runtimeService->messageEventReceived('pizzaReceived', $target->getId(), [
						'pizzaServiceProcessId' => $event->execution->getProcessInstanceId()
					]);
					break;
				case 'payForPizza':
					$id = $event->variables['pizzaServiceProcessId'];
					$target = $this->runtimeService->createExecutionQuery()
								   ->processInstanceId($id)
								   ->messageEventSubscriptionName('pizzaPaymentReceived')
								   ->findOne();
					
					$this->runtimeService->messageEventReceived('pizzaPaymentReceived', $target->getId());
					break;
			}
		});
		
		$process = $this->runtimeService->startProcessInstanceByKey('CustomerOrdersPizza', $businessKey);
		$this->assertEquals('choosePizzaTask', $process->getActivityId());
		$this->assertEquals($businessKey, $process->getBusinessKey());
		$this->assertFalse($process->isEnded());
		
		$task = $this->taskService->createTaskQuery()->findOne();
		$this->assertEquals('choosePizzaTask', $task->getActivityId());
		
		$this->taskService->complete($task->getId(), []);
		$this->assertEquals(1, $this->taskService->createTaskQuery()->count());
		$task = $this->taskService->createTaskQuery()->findOne();
		$this->assertEquals('preparePizzaTask', $task->getActivityId());
		
		$this->taskService->complete($task->getId(), []);
		$this->assertEquals(1, $this->runtimeService->createExecutionQuery()->count());
		$this->assertEquals(1, $this->taskService->createTaskQuery()->count());
		$task = $this->taskService->createTaskQuery()->findOne();
		$this->assertEquals('fileReportTask', $task->getActivityId());
		
		$process = $this->runtimeService->createExecutionQuery()->findOne();
		
		$this->taskService->complete($task->getId(), []);
		$this->assertEquals(0, $this->runtimeService->createExecutionQuery()->count());
	}
}
