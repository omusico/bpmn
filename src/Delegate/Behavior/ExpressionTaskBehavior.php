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

use KoolKode\BPMN\Engine\AbstractBehavior;
use KoolKode\BPMN\Engine\VirtualExecution;
use KoolKode\Expression\ExpressionInterface;

/**
 * Implements service task behavior using an expression parsed from a BPMN process definition.
 * 
 * @author Martin Schröder
 */
class ExpressionTaskBehavior extends AbstractBehavior
{
	protected $expression;
	
	protected $resultVariable;
	
	protected $name;
	
	public function __construct(ExpressionInterface $expression, ExpressionInterface $name)
	{
		$this->expression = $expression;
		$this->name = $name;
	}
	
	public function setResultVariable($var = NULL)
	{
		$this->resultVariable = ($var === NULL) ? NULL : (string)$var;
	}
	
	public function executeBehavior(VirtualExecution $execution)
	{
		$execution->getEngine()->debug('Execute expression in service task "{task}"', [
			'task' => (string)call_user_func($this->name, $execution->getExpressionContext())
		]);
		
		$result = call_user_func($this->expression, $execution->getExpressionContext());
		
		if($this->resultVariable !== NULL)
		{
			$execution->setVariable($this->resultVariable, $result);
		}
		
		$execution->takeAll(NULL, [$execution]);
	}
}
