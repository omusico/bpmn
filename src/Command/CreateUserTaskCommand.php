<?php

/*
 * This file is part of KoolKode BPMN.
*
* (c) Martin Schröder <m.schroeder2007@gmail.com>
*
* For the full copyright and license information, please view the LICENSE
* file that was distributed with this source code.
*/

namespace KoolKode\BPMN\Command;

use KoolKode\BPMN\CommandContext;
use KoolKode\BPMN\InternalExecution;
use KoolKode\Util\UUID;

class CreateUserTaskCommand extends AbstractCommand
{
	protected $name;
	protected $execution;
	
	public function __construct($name, InternalExecution $execution)
	{
		$this->name = (string)$name;
		$this->execution = $execution;
	}
	
	public function getPriority()
	{
		return 1500;
	}
	
	public function execute(CommandContext $context)
	{
		$conn = $context->getDatabaseConnection();
		
		$sql = "	INSERT INTO `#__bpm_user_task`
						(`id`, `execution_id`, `name`, `activity`, `created_at`)
					VALUES
						(:id, :eid, :name, :activity, :created)
		";
		$stmt = $conn->prepare($sql);
		$stmt->bindValue('id', UUID::createRandom()->toBinary());
		$stmt->bindValue('eid', $this->execution->getId()->toBinary());
		$stmt->bindValue('name', $this->name);
		$stmt->bindValue('activity', $this->execution->getNode()->getId());
		$stmt->bindValue('created', time());
		$stmt->execute();
	}
}