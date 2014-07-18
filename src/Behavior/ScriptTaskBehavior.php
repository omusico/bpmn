<?php

/*
 * This file is part of KoolKode BPMN.
*
* (c) Martin Schröder <m.schroeder2007@gmail.com>
*
* For the full copyright and license information, please view the LICENSE
* file that was distributed with this source code.
*/

namespace KoolKode\BPMN\Behavior;

use KoolKode\Process\ActivityInterface;
use KoolKode\Process\Execution;

class ScriptTaskBehavior implements ActivityInterface
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
	
	public function execute(Execution $execution)
	{
		eval($this->script);
	}
}
