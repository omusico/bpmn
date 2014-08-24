<?php

/*
 * This file is part of KoolKode BPMN.
*
* (c) Martin Schröder <m.schroeder2007@gmail.com>
*
* For the full copyright and license information, please view the LICENSE
* file that was distributed with this source code.
*/

namespace KoolKode\BPMN\Repository;

use KoolKode\BPMN\Runtime\Behavior\MessageStartEventBehavior;
use KoolKode\BPMN\Runtime\Behavior\NoneStartEventBehavior;
use KoolKode\BPMN\Runtime\Behavior\SignalStartEventBehavior;
use KoolKode\Process\ProcessDefinition;
use KoolKode\Util\Uuid;

class BusinessProcessDefinition implements \JsonSerializable
{
	protected $id;
	protected $key;
	protected $name;
	protected $revision;
	protected $model;
	protected $deployed;
	
	public function __construct(UUID $id, $key, $revision, ProcessDefinition $model, $name, \DateTimeImmutable $deployed)
	{
		$this->id = $id;
		$this->key = $key;
		$this->name = $name;
		$this->revision = (int)$revision;
		$this->model = $model;
		$this->deployed = $deployed;
	}
	
	public function jsonSerialize()
	{
		return [
			'id' => (string)$this->id,
			'key' => $this->key,
			'revision' => $this->revision,
			'name' => $this->name,
			'deployDate' => $this->deployed->format(\DateTime::ISO8601)
		];
	}
	
	public function getId()
	{
		return $this->id;
	}
	
	public function getKey()
	{
		return $this->key;
	}
	
	public function getName()
	{
		return $this->name;
	}
	
	public function getRevision()
	{
		return $this->revision;
	}
	
	public function getModel()
	{
		return unserialize(serialize($this->model));
	}
	
	public function getDeployed()
	{
		return clone $this->deployed;
	}
	
	public function findNoneStartEvent()
	{
		foreach($this->model->findStartNodes() as $node)
		{
			$behavior = $node->getBehavior();
			
			if($behavior instanceof NoneStartEventBehavior && !$behavior->isSubProcessStart())
			{
				return $node;
			}
		}
		
		throw new \OutOfBoundsException(sprintf('No none start event found in "%s" revision %u', $this->key, $this->revision));
	}
	
	public function findMessageStartEvent($messageName)
	{
		$messageName = (string)$messageName;
		
		foreach($this->model->findStartNodes() as $node)
		{
			$behavior = $node->getBehavior();
			
			if($behavior instanceof MessageStartEventBehavior && !$behavior->isSubProcessStart())
			{
				if($behavior->getMessageName() == $messageName)
				{
					return $node;
				}
			}
		}
		
		throw new \OutOfBoundsException(sprintf('No "%s" message start event found in "%s" revision %u', $messageName, $this->key, $this->revision));
	}
	
	public function findSignalStartEvent($signalName)
	{
		$signalName = (string)$signalName;
	
		foreach($this->model->findStartNodes() as $node)
		{
			$behavior = $node->getBehavior();
				
			if($behavior instanceof SignalStartEventBehavior && !$behavior->isSubProcessStart())
			{
				if($behavior->getSignalName() == $signalName)
				{
					return $node;
				}
			}
		}
	
		throw new \OutOfBoundsException(sprintf('No "%s" signal start event found in "%s" revision %u', $signalName, $this->key, $this->revision));
	}
}
