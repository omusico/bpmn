<?php

/*
 * This file is part of KoolKode BPMN.
*
* (c) Martin Schröder <m.schroeder2007@gmail.com>
*
* For the full copyright and license information, please view the LICENSE
* file that was distributed with this source code.
*/

namespace KoolKode\BPMN\Repository\Command;

use KoolKode\BPMN\BusinessProcessBuilder;
use KoolKode\BPMN\Engine\AbstractBusinessCommand;
use KoolKode\BPMN\Engine\BinaryData;
use KoolKode\BPMN\Engine\ProcessEngine;
use KoolKode\BPMN\Repository\BusinessProcessDefinition;
use KoolKode\BPMN\Runtime\Behavior\MessageStartEventBehavior;
use KoolKode\BPMN\Runtime\Behavior\SignalStartEventBehavior;
use KoolKode\Util\UUID;

/**
 * Deploys a business process and takes care of versioning and start event subscriptions.
 * 
 * @author Martin Schröder
 */
class DeployBusinessProcessCommand extends AbstractBusinessCommand
{
	protected $builder;
	
	protected $deploymentId;
	
	public function __construct(BusinessProcessBuilder $builder, UUID $deploymentId = NULL)
	{
		$this->builder = $builder;
		$this->deploymentId = $deploymentId;
	}
	
	public function getPriority()
	{
		return self::PRIORITY_DEFAULT * 2;
	}
	
	public function executeCommand(ProcessEngine $engine)
	{
		$sql = "	SELECT `revision`
					FROM `#__process_definition`
					WHERE `process_key` = :key
					ORDER BY `revision` DESC
		";
		$stmt = $engine->prepareQuery($sql);
		$stmt->bindValue('key', $this->builder->getKey());
		$stmt->setLimit(1);
		$stmt->execute();
		$revision = $stmt->fetchNextColumn(0);
			
		$model = $this->builder->build();
		$id = $model->getId();
		$time = time();
			
		$sql = "	INSERT INTO `#__process_definition`
						(`id`, `deployment_id`, `process_key`, `revision`, `definition`, `name`, `deployed_at`)
					VALUES
						(:id, :deployment, :key, :revision, :model, :name, :deployed)
		";
		$stmt = $engine->prepareQuery($sql);
		$stmt->bindValue('id', $id);
		$stmt->bindValue('deployment', $this->deploymentId);
		$stmt->bindValue('key', $this->builder->getKey());
		$stmt->bindValue('revision', $revision + 1);
		$stmt->bindValue('model', new BinaryData(serialize($model), 3));
		$stmt->bindValue('name', $model->getTitle());
		$stmt->bindValue('deployed', $time);
		$stmt->execute();
		
		$sql = "	DELETE FROM `#__process_subscription`
					WHERE `definition_id` IN (
						SELECT `id`
						FROM `#__process_definition`
						WHERE `process_key` = :key
					)
		";
		$stmt = $engine->prepareQuery($sql);
		$stmt->bindValue('key', $this->builder->getKey());
		$stmt->execute();
		
		$engine->info('Deployed business process {key} revision {revision} using id {id}', [
			'key' => $this->builder->getKey(),
			'revision' => $revision + 1,
			'id' => (string)$id
		]);
		
		foreach($model->findStartNodes() as $node)
		{
			$behavior = $node->getBehavior();
			
			if($behavior instanceof MessageStartEventBehavior && !$behavior->isSubProcessStart())
			{
				$sql = "	INSERT INTO `#__process_subscription`
								(`id`, `definition_id`, `flags`, `name`)
							VALUES
								(:id, :def, :flags, :message)
				";
				$stmt = $engine->prepareQuery($sql);
				$stmt->bindValue('id', UUID::createRandom());
				$stmt->bindValue('def', $id);
				$stmt->bindValue('flags', ProcessEngine::SUB_FLAG_MESSAGE);
				$stmt->bindValue('message', $behavior->getMessageName());
				$stmt->execute();
				
				$engine->debug('Process {process} subscribed to message <{message}>', [
					'process' => $this->builder->getKey(),
					'message' => $behavior->getMessageName()
				]);
			}
			
			if($behavior instanceof SignalStartEventBehavior && !$behavior->isSubProcessStart())
			{
				$sql = "	INSERT INTO `#__process_subscription`
								(`id`, `definition_id`, `flags`, `name`)
							VALUES
								(:id, :def, :flags, :message)
				";
				$stmt = $engine->prepareQuery($sql);
				$stmt->bindValue('id', UUID::createRandom());
				$stmt->bindValue('def', $id);
				$stmt->bindValue('flags', ProcessEngine::SUB_FLAG_SIGNAL);
				$stmt->bindValue('message', $behavior->getSignalName());
				$stmt->execute();
				
				$engine->debug('Process {process} subscribed to signal <{signal}>', [
					'process' => $this->builder->getKey(),
					'signal' => $behavior->getSignalName()
				]);
			}
		}
		
		return new BusinessProcessDefinition(
			$id,
			$this->builder->getKey(),
			$revision + 1,
			$model,
			$model->getTitle(),
			new \DateTimeImmutable('@' . $time)
		);
	}
}
