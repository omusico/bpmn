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
use KoolKode\BPMN\Test\BusinessProcessTestCase;
use KoolKode\BPMN\Test\MessageHandler;

class PizzaCollaborationTest extends BusinessProcessTestCase
{	
	public function testPizzaProcess()
	{
		$this->deployFile('PizzaCollaborationTest.bpmn');
		
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
	
	/**
	 * @MessageHandler("sendPizzaOrder", processKey = "CustomerOrdersPizza")
	 * 
	 * @param MessageThrownEvent $event
	 */
	public function sendPizzaOrder(MessageThrownEvent $event)
	{
		$process = $this->runtimeService->startProcessInstanceByMessage('pizzaOrderReceived', $event->execution->getBusinessKey());
		$this->assertTrue($process instanceof ExecutionInterface);
		$this->assertTrue($process->isProcessInstance());
		$this->assertEquals('PizzaServiceDeliversPizza', $process->getProcessDefinition()->getKey());
	}
	
	/**
	 * @MessageHandler("deliverPizza", processKey = "PizzaServiceDeliversPizza")
	 * 
	 * @param MessageThrownEvent $event
	 */
	public function deliverPizza(MessageThrownEvent $event)
	{
		$this->runtimeService->createMessageCorrelation('pizzaReceived')
							 ->processBusinessKey($event->execution->getBusinessKey())
							 ->correlate();
	}
	
	/**
	 * @MessageHandler("payForPizza", processKey = "CustomerOrdersPizza")
	 * 
	 * @param MessageThrownEvent $event
	 */
	public function payForPizza(MessageThrownEvent $event)
	{
		$this->runtimeService->createMessageCorrelation('pizzaPaymentReceived')
							 ->processBusinessKey($event->execution->getBusinessKey())
							 ->correlate();
	}
}
