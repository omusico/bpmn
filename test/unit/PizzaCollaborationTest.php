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
use KoolKode\BPMN\Runtime\ExecutionInterface;

class PizzaCollaborationTest extends BusinessProcessTestCase
{	
	public function testPizzaProcess()
	{
		$this->deployFile('PizzaCollaboration.bpmn');
		
		$this->registerMessageHandler('CustomerOrdersPizza', 'sendPizzaOrder', function(MessageThrownEvent $event) {
			
			$process = $this->runtimeService->startProcessInstanceByMessage('pizzaOrderReceived', $event->execution->getBusinessKey());
			$this->assertTrue($process instanceof ExecutionInterface);
			$this->assertTrue($process->isProcessInstance());
			$this->assertEquals('PizzaServiceDeliversPizza', $process->getProcessDefinition()->getKey());
		});
		
		$this->registerMessageHandler('PizzaServiceDeliversPizza', 'deliverPizza', function(MessageThrownEvent $event) {
			
			$this->runtimeService->createMessageCorrelation('pizzaReceived')
								 ->processBusinessKey($event->execution->getBusinessKey())
								 ->correlate();
		});
		
		$this->registerMessageHandler('CustomerOrdersPizza', 'payForPizza', function(MessageThrownEvent $event) {
			
			$this->runtimeService->createMessageCorrelation('pizzaPaymentReceived')
								 ->processBusinessKey($event->execution->getBusinessKey())
								 ->correlate();
		});
		
		$process = $this->runtimeService->startProcessInstanceByKey('CustomerOrdersPizza', 'Pizza Funghi');
		$this->assertTrue($process instanceof ExecutionInterface);
		$this->assertTrue($process->isProcessInstance());
		$this->assertEquals('choosePizzaTask', $process->getActivityId());
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
