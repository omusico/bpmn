<?php

/*
 * This file is part of KoolKode BPMN.
*
* (c) Martin Schröder <m.schroeder2007@gmail.com>
*
* For the full copyright and license information, please view the LICENSE
* file that was distributed with this source code.
*/

namespace KoolKode\BPMN\Behavior;

use KoolKode\BPMN\Command\CreateSignalSubscriptionCommand;
use KoolKode\Process\Execution;
use KoolKode\Process\SignalableBehaviorInterface;

class AwaitSignalBehavior implements SignalableBehaviorInterface
{
	public function execute(Execution $execution)
	{
		$execution->waitForSignal();
		$execution->getProcessEngine()->pushCommand(new CreateSignalSubscriptionCommand('', $execution));
	}
	
	public function signal(Execution $execution, $signal, array $variables = [])
	{
		foreach($variables as $k => $v)
		{
			$execution->setVariable($k, $v);
		}
		
		return $execution->takeAll(NULL, [$execution]);
	}
}
