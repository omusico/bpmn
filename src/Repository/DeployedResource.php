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

use KoolKode\Util\UUID;

class DeployedResource implements \JsonSerializable
{
	protected $id;
	
	protected $name;
	
	protected $deployment;
	
	public function __construct(Deployment $deployment, UUID $id, $name)
	{
		$this->deployment = $deployment;
		$this->id = $id;
		$this->name = (string)$name;
	}
	
	public function jsonSerialize()
	{
		return [
			'id' => (string)$this->id,
			'name' => $this->name
		];
	}
}
