<?php

/*
 * This file is part of KoolKode BPMN.
*
* (c) Martin Schröder <m.schroeder2007@gmail.com>
*
* For the full copyright and license information, please view the LICENSE
* file that was distributed with this source code.
*/

namespace KoolKode\BPMN\Delegate\Behavior;

use KoolKode\BPMN\Engine\AbstractScopeBehavior;
use KoolKode\BPMN\Engine\VirtualExecution;
use KoolKode\BPMN\Runtime\Command\SignalExecutionCommand;

/**
 * Executes a PHP script defined in a task within a BPMN process.
 * 
 * @author Martin Schröder
 */
class ScriptTaskBehavior extends AbstractScopeBehavior
{
	protected $language;
	
	protected $resultVariable;
	
	protected $script;
	
	public function __construct($language, $script)
	{
		$this->language = strtolower($language);
		$this->script = (string)$script;
				
		if($this->language !== 'php')
		{
			throw new \InvalidArgumentException(sprintf('Only PHP is supported as scripting language, given "%s"', $this->language));
		}
	}
	
	public function setResultVariable($var = NULL)
	{
		$this->resultVariable = ($var === NULL) ? NULL : (string)$var;
	}
	
	public function executeBehavior(VirtualExecution $execution)
	{
		$execution->getEngine()->debug('Evaluate <{language}> script task "{task}"', [
			'language' => $this->language,
			'task' => $this->getStringValue($this->name, $execution->getExpressionContext())
		]);
		
		$result = eval($this->script);
		
		if($this->resultVariable !== NULL)
		{
			$execution->setVariable($this->resultVariable, $result);
		}
		
		$execution->getEngine()->pushCommand(new SignalExecutionCommand($execution));
		$execution->waitForSignal();
	}
}
