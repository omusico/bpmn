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
 * Registers a method as BPMN 2.0 message handler.
 * 
 * @Annotation
 * 
 * @author Martin Schröder
 */
final class MessageHandler
{
	public $value;
	
	public $processKey;
	
	public function __construct($messageName, $processKey = NULL)
	{
		$this->value = (string)$messageName;
		$this->processKey = ($processKey === NULL) ? NULL : (string)$processKey;
	}
}
