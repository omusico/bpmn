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

use KoolKode\BPMN\Delegate\DelegateExecutionInterface;
use KoolKode\BPMN\Task\TaskInterface;

class ScriptTaskTest extends BusinessProcessTestCase
{
	public function provideNumbers()
	{
		return [
			[3, 5, 8],
			[3, -5, -2],
			[3.5, 5.5, 9]
		];
	}
	
	/**
	 * @dataProvider provideNumbers
	 */
	public function testCanAddNumbers($a, $b, $result)
	{
		$this->deployFile('ScriptTaskTest.bpmn');
		
		$this->registerServiceTaskHandler('main', 'ServiceTask_1', function(DelegateExecutionInterface $execution) use($result) {
			$this->assertEquals($result, $execution->getVariable('result'));
		});
		
		$process = $this->runtimeService->startProcessInstanceByKey('main');
		
		$task = $this->taskService->createTaskQuery()->findOne();
		$this->assertTrue($task instanceof TaskInterface);
		
		$this->taskService->complete($task->getId(), [
			'a' => $a,
			'b' => $b
		]);
		
		$this->assertEquals(0, $this->runtimeService->createExecutionQuery()->count());
	}
}
