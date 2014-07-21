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

class UpdateBusinessKeyCommand extends AbstractCommand
{
	protected $processInstanceId;
	protected $businessKey;
	
	public function __construct(UUID $processInstanceId, $businessKey)
	{
		$this->processInstanceId = $processInstanceId;
		$this->businessKey = ($businessKey === NULL) ? NULL : (string)$businessKey;
	}
	
	public function execute(CommandContext $context)
	{
		$sql = "	UPDATE `#__bpm_execution`
					SET `business_key` = :key
					WHERE `process_id` = :pid
		";
		$stmt = $context->prepareQuery($sql);
		$stmt->bindValue('key', $this->businessKey);
		$stmt->bindValue('pid', $this->processInstanceId->toBinary());
		$stmt->execute();
	}
}
