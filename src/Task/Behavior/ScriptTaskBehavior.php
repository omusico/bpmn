<?php

/*
 * This file is part of KoolKode BPMN.
*
* (c) Martin Schröder <m.schroeder2007@gmail.com>
*
* For the full copyright and license information, please view the LICENSE
* file that was distributed with this source code.
*/

namespace KoolKode\BPMN\Task\Behavior;

use KoolKode\BPMN\Engine\AbstractBehavior;
use KoolKode\BPMN\Engine\VirtualExecution;

class ScriptTaskBehavior extends AbstractBehavior
{
	protected $name;
	protected $language;
	protected $script;
	
	public function __construct($language, $script, $name = '')
	{
		$this->language = (string)$language;
		$this->script = (string)$script;
		$this->name = (string)$name;
	}
	
	protected function executeBehavior(VirtualExecution $execution)
	{
		eval($this->script);
	}
}
