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
use KoolKode\Expression\ExpressionInterface;

class ScriptTaskBehavior extends AbstractBehavior
{
	protected $name;
	protected $language;
	protected $script;
	
	public function __construct($language, $script, ExpressionInterface $name)
	{
		$this->language = (string)$language;
		$this->script = (string)$script;
		$this->name = $name;
	}
	
	protected function executeBehavior(VirtualExecution $execution)
	{
		$execution->getEngine()->debug('Evaluate {language} script task "{task}"', [
			'language' => $this->language,
			'task' => (string)call_user_func($this->name, $execution->getExpressionContext())
		]);
		
		eval($this->script);
	}
}
