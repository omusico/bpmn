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
use KoolKode\Expression\ExpressionInterface;

/**
 * Implements service task behavior using an expression parsed from a BPMN process definition.
 * 
 * @author Martin Schröder
 */
class ExpressionTaskBehavior extends AbstractScopeBehavior
{
	protected $expression;
	
	protected $resultVariable;
	
	public function __construct(ExpressionInterface $expression)
	{
		$this->expression = $expression;
	}
	
	public function setResultVariable($var = NULL)
	{
		$this->resultVariable = ($var === NULL) ? NULL : (string)$var;
	}
	
	public function executeBehavior(VirtualExecution $execution)
	{
		$this->createScopedEventSubscriptions($execution);
		
		$execution->getEngine()->debug('Execute expression in service task "{task}"', [
			'task' => $this->getStringValue($this->name, $execution->getExpressionContext())
		]);
		
		$result = $this->getValue($this->expression, $execution->getExpressionContext());
		
		if($this->resultVariable !== NULL)
		{
			$execution->setVariable($this->resultVariable, $result);
		}
		
		$execution->getEngine()->pushCommand(new SignalExecutionCommand($execution));
		$execution->waitForSignal();
	}
}
