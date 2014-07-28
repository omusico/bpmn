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

class ExpressionTaskBehavior extends AbstractBehavior
{
	protected $expression;
	
	protected $name;
	
	public function __construct(ExpressionInterface $expression, ExpressionInterface $name)
	{
		$this->expression = $expression;
		$this->name = $name;
	}
	
	public function executeBehavior(VirtualExecution $execution)
	{
		$execution->getEngine()->debug('Execute delegate expression in "{task}"', [
			'task' => (string)call_user_func($this->name, $execution->getExpressionContext())
		]);
		
		call_user_func($this->expression, $execution->getExpressionContext());
		
		$execution->takeAll(NULL, [$execution]);
	}
}
