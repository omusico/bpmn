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

/**
 * Provides the public API of a BPMN 2.0 process engine.
 * 
 * @author Martin Schröder
 */
interface ProcessEngineInterface
{
	public function getRepositoryService();
	
	public function getRuntimeService();
	
	public function getTaskService();
}
