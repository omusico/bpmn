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

use KoolKode\BPMN\Runtime\ExecutionInterface;
use KoolKode\BPMN\Task\TaskInterface;
use KoolKode\BPMN\Test\BusinessProcessTestCase;

class SimpleProcessTest extends BusinessProcessTestCase
{
	public function provideSimpleTestData()
	{
		return [
			[50, 20, 50],
			[100, 20, 100],
			[160, 20, 140],
			[160, 25, 135]
		];
	}
	
	/**
	 * @dataProvider provideSimpleTestData
	 */
	public function testSimpleTest($amount, $discount, $result)
	{
		$this->deployFile('SimpleProcessTest.bpmn');
		
		$bill = new ComputeBillTask();
		$this->delegateTasks->registerTask($bill);
		
		$process = $this->runtimeService->startProcessInstanceByKey('SimpleTestProcess', 'XYZ');
		$this->assertTrue($process instanceof ExecutionInterface);
		$this->assertTrue($process->isProcessInstance());
		$this->assertEquals('XYZ', $process->getBusinessKey());
		$this->assertNull($process->getParentId());
		$this->assertEquals($process->getId(), $process->getProcessInstanceId());
		$this->assertEquals('amountTask', $process->getActivityId());
		$this->assertFalse($process->isEnded());
		
		$task = $this->taskService->createTaskQuery()->taskDefinitionKey('amountTask')->findOne();
		$this->assertTrue($task instanceof TaskInterface);
		$this->assertEquals('amountTask', $task->getActivityId());
		$this->assertEquals('Enter amount', $task->getName());
		$this->assertEquals($process->getId(), $task->getExecutionId());
		$this->assertNull($task->getAssignee());
		$this->assertNull($task->getClaimDate());
		
		$this->taskService->complete($task->getId(), [
			'amount' => $amount
		]);
		$this->assertEquals(1, $this->runtimeService->createExecutionQuery()->variableValueEqualTo('amount', $amount)->count());
		
		foreach($this->taskService->createTaskQuery()->taskDefinitionKey('discountTask')->findAll() as $task)
		{
			$this->assertTrue($task instanceof TaskInterface);
			$this->assertEquals('discountTask', $task->getActivityId());
			$this->assertEquals('Calculate granted discount', $task->getName());
			$this->assertEquals($process->getId(), $task->getExecutionId());
			$this->assertNull($task->getAssignee());
			$this->assertNull($task->getClaimDate());
			
			$this->taskService->complete($task->getId(), [
				'discount' => $discount
			]);
		}
		
		$process = $this->runtimeService->createExecutionQuery()->findOne();
		
		$this->runtimeService->signalEventReceived('collectAllPendingBills');
		
		$this->assertEquals($result, $bill->result);
		$this->assertEquals(0, $this->runtimeService->createExecutionQuery()->count());
	}
}
