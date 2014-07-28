<?php

/*
 * This file is part of KoolKode BPMN.
 *
 * (c) Martin Schröder <m.schroeder2007@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace KoolKode\BPMN\Engine;

use KoolKode\BPMN\Repository\RepositoryService;
use KoolKode\BPMN\Runtime\RuntimeService;
use KoolKode\BPMN\Task\TaskService;

/**
 * Provides the public API of a BPMN 2.0 process engine.
 * 
 * @author Martin Schröder
 */
interface ProcessEngineInterface
{
	/**
	 * @return RepositoryService
	 */
	public function getRepositoryService();
	
	/**
	 * @return RuntimeService
	 */
	public function getRuntimeService();
	
	/**
	 * @return TaskService
	 */
	public function getTaskService();
}
