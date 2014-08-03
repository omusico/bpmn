<?php

/*
 * This file is part of KoolKode BPMN.
*
* (c) Martin Schröder <m.schroeder2007@gmail.com>
*
* For the full copyright and license information, please view the LICENSE
* file that was distributed with this source code.
*/

namespace KoolKode\BPMN\Runtime\Command;

use KoolKode\BPMN\Engine\AbstractBusinessCommand;
use KoolKode\BPMN\Engine\ProcessEngine;
use KoolKode\BPMN\Engine\VirtualExecution;

class ClearEventSubscriptionsCommand extends AbstractBusinessCommand
{
	protected $execution;
	
	public function __construct(VirtualExecution $execution)
	{
		$this->execution = $execution;
	}
	
	public function getPriority()
	{
		return self::PRIORITY_DEFAULT * 10;
	}
	
	public function executeCommand(ProcessEngine $engine)
	{
		$sql = "	DELETE FROM `#__bpm_event_subscription`
					WHERE `execution_id` = :eid
		";
		$stmt = $engine->prepareQuery($sql);
		$stmt->bindValue('eid', $this->execution->getId()->toBinary());
		$stmt->execute();
		
		$count = (int)$stmt->rowCount();
		
		if($count > 0)
		{
			$message = sprintf('Cleared {count} event subscription%s related to {execution}', ($count == 1) ? '' : 's');
			
			$engine->debug($message, [
				'count' => $count,
				'execution' => (string)$this->execution
			]);
		}
	}
}
