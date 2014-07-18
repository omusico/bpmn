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
use KoolKode\Util\Uuid;

class SetProcessVariableCommand extends AbstractCommand
{
	protected $executionId;
	protected $variableName;
	protected $variableValue;
	
	public function __construct(UUID $executionId, $variableName, $variableValue)
	{
		$this->executionId = $executionId;
		$this->variableName = (string)$variableName;
		$this->variableValue = $variableValue;
	}
	
	public function execute(CommandContext $context)
	{
		$conn = $context->getDatabaseConnection();
		$sql = "	SELECT e.*, d.`definition`
					FROM `#__bpm_execution` AS e
					INNER JOIN `#__bpm_process_definition` AS d ON (d.`id` = e.`definition_id`)
					WHERE e.`id` = :id
		";
		$stmt = $conn->prepare($sql);
		$stmt->bindValue('id', $this->executionId->toBinary());
		$stmt->execute();
		$row = $stmt->fetch(\PDO::FETCH_ASSOC);
			
		$execution = $context->getProcessEngine()->unserializeExecution($row);
			
		if($this->variableValue !== NULL)
		{
			$execution->setVariable($this->variableName, $this->variableValue);
		}
		else
		{
			$execution->removeVariable($this->variableName);
		}
	}
}
