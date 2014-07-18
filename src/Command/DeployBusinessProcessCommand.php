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

use KoolKode\BPMN\Behavior\MessageStartEventBehavior;
use KoolKode\BPMN\BusinessProcessDefinition;
use KoolKode\BPMN\BusinessProcessBuilder;
use KoolKode\BPMN\CommandContext;
use KoolKode\BPMN\ProcessEngine;
use KoolKode\Util\Uuid;

class DeployBusinessProcessCommand extends AbstractCommand
{
	protected $builder;
	
	public function __construct(BusinessProcessBuilder $builder)
	{
		$this->builder = $builder;
	}
	
	public function execute(CommandContext $context)
	{
		$conn = $context->getDatabaseConnection();
		
		$sql = "	SELECT `revision`
					FROM `#__bpm_process_definition`
					WHERE `process_key` = :key
					ORDER BY `revision` DESC
					LIMIT 1
		";
		$stmt = $conn->prepare($sql);
		$stmt->bindValue('key', $this->builder->getKey());
		$stmt->execute();
		$revision = $stmt->fetchColumn(0) ?: 0;
			
		$model = $this->builder->build();
		$id = $model->getId();
		$time = time();
			
		$sql = "	INSERT INTO `#__bpm_process_definition`
						(`id`, `process_key`, `revision`, `definition`, `name`, `deployed_at`)
					VALUES
						(:id, :key, :revision, :model, :name, :deployed)
		";
		$stmt = $conn->prepare($sql);
		$stmt->bindValue('id', $id->toBinary());
		$stmt->bindValue('key', $this->builder->getKey());
		$stmt->bindValue('revision', $revision + 1);
		$stmt->bindValue('model', gzcompress(serialize($model), 3));
		$stmt->bindValue('name', $model->getTitle());
		$stmt->bindValue('deployed', $time);
		$stmt->execute();
		
		// TODO: Subscribe to signal start events...
		
		foreach($model->findStartNodes() as $node)
		{
			$behavior = $node->getBehavior();
			
			if($behavior instanceof MessageStartEventBehavior)
			{
				$sql = "	DELETE FROM `#__bpm_process_subscription`
							WHERE `definition_id` IN (
								SELECT `id`
								FROM `#__bpm_process_definition`
								WHERE `process_key` = :key
							)
				";
				$stmt = $conn->prepare($sql);
				$stmt->bindValue('key', $this->builder->getKey());
				$stmt->execute();
				
				$sql = "	INSERT INTO `#__bpm_process_subscription`
								(`id`, `definition_id`, `flags`, `name`)
							VALUES
								(:id, :def, :flags, :message)
				";
				$stmt = $conn->prepare($sql);
				$stmt->bindValue('id', UUID::createRandom()->toBinary());
				$stmt->bindValue('def', $id->toBinary());
				$stmt->bindValue('flags', ProcessEngine::SUB_FLAG_MESSAGE);
				$stmt->bindValue('message', $behavior->getMessageName());
				$stmt->execute();
			}
		}
		
		return new BusinessProcessDefinition(
			$id,
			$this->builder->getKey(),
			$revision + 1,
			$model,
			$model->getTitle(),
			new \DateTime('@' . $time)
		);
	}
}
