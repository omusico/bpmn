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
use KoolKode\Process\Node;
use KoolKode\Util\Uuid;

/**
 * Creates a message event subscription.
 * 
 * @author Martin Schröder
 */
class CreateMessageSubscriptionCommand extends AbstractBusinessCommand
{
	protected $message;
	
	protected $execution;
	
	protected $node;
	
	public function __construct($message, VirtualExecution $execution, Node $node = NULL)
	{
		$this->message = (string)$message;
		$this->execution = $execution;
		$this->node = $node;
	}
	
	public function executeCommand(ProcessEngine $engine)
	{
		$sql = "	INSERT INTO `#__bpm_event_subscription`
						(`id`, `execution_id`, `node`, `process_instance_id`, `flags`, `name`, `created_at`)
					VALUES
						(:id, :eid, :node, :pid, :flags, :message, :created)
		";
		$stmt = $engine->prepareQuery($sql);
		$stmt->bindValue('id', UUID::createRandom()->toBinary());
		$stmt->bindValue('eid', $this->execution->getId()->toBinary());
		$stmt->bindValue('node', ($this->node === NULL) ? NULL : $this->node->getId());
		$stmt->bindValue('pid', $this->execution->getRootExecution()->getId()->toBinary());
		$stmt->bindValue('flags', ProcessEngine::SUB_FLAG_MESSAGE);
		$stmt->bindValue('message', $this->message);
		$stmt->bindValue('created', time());
		$stmt->execute();
		
		$engine->debug('{execution} subscribed to message <{message}>', [
			'execution' => (string)$this->execution,
			'message' => $this->message
		]);
	}
}
