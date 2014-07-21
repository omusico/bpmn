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

class CallbackCommand extends AbstractCommand
{
	protected $callback;
	
	protected $priority;
	
	public function __construct(callable $callback, $priority = self::PRIORITY_DEFAULT)
	{
		$this->callback = $callback;
		$this->priority = (int)$priority;
	}
	
	public function execute(CommandContext $context)
	{
		return call_user_func($this->callback, $context);
	}
}
