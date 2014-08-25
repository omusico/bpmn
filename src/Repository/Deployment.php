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

class Deployment
{
	protected $id;
	
	protected $name;
	
	protected $deployDate;
	
	public function __construct(UUID $id, $name, \DateTimeImmutable $deployDate)
	{
		$this->id = $id;
		$this->name = (string)$name;
		$this->deployDate = $deployDate;
	}
	
	public function getId()
	{
		return $this->id;
	}
	
	public function getName()
	{
		return $this->name;
	}
	
	public function getDeployDate()
	{
		return $this->deployDate;
	}
}
