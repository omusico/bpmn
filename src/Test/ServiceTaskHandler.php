<?php

/*
 * This file is part of KoolKode BPMN.
 *
 * (c) Martin Schröder <m.schroeder2007@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace KoolKode\BPMN\Test;

/**
 * Registers a method as BPMN 2.0 service task handler.
 * 
 * @Annotation
 * 
 * @author Martin Schröder
 */
final class ServiceTaskHandler
{
	public $value;
	
	public $processKey;
	
	public function __construct($serviceTask, $processKey = NULL)
	{
		$this->value = (string)$serviceTask;
		$this->processKey = ($processKey === NULL) ? NULL : (string)$processKey;
	}
}
