<?php

namespace KoolKode\BPMN;

use KoolKode\BPMN\Event\MessageThrowEvent;

class PizzaCollaborationTest extends BusinessProcessTestCase
{
	public function testPizzaProcess()
	{
		$this->repositoryService->deployDiagram('PizzaCollaboration.bpmn');
		
		$businessKey = 'Pizza Funghi';
		
		$this->eventDispatcher->connect(function(MessageThrowEvent $event) use($businessKey) {
			
			switch($event->getActivityId())
			{
				case 'sendPizzaOrder':
					$this->runtimeService->startProcessInstanceByMessage('pizzaOrderReceived', $businessKey, [
						'csustomerProcessId' => $event->getProcessInstanceId()
					]);
					break;
				case 'deliverPizza':
					$id = $event->getVariables()['csustomerProcessId'];
					$target = $this->runtimeService->createExecutionQuery()
								   ->processInstanceId($id)
								   ->messageEventSubscriptionName('pizzaReceived')
								   ->findOne();
					
					$this->runtimeService->messageEventReceived('pizzaReceived', $target->getId(), [
						'pizzaServiceProcessId' => $event->getProcessInstanceId()
					]);
					break;
				case 'payForPizza':
					$id = $event->getVariables()['pizzaServiceProcessId'];
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
