<?php

/*
 * This file is part of KoolKode BPMN.
 *
 * (c) Martin Schröder <m.schroeder2007@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace KoolKode\BPMN\Runtime;

class QueryVariableValue
{
	protected $name;
	
	protected $value;
	
	protected $operator;
	
	public function __construct($name, $value, $operator)
	{
		$this->name = (string)$name;
		$this->value = $value;
		
		switch($operator)
		{
			case '=':
			case '<>':
			case 'LIKE':
			case 'NOT LIKE':
			case '<':
			case '<=':
			case '>':
			case '>=':
				$this->operator = (string)$operator;
				break;
			default:
				throw new \InvalidArgumentException(sprintf('Unsupported variable query operator: "%s"', $operator));
		}
	}
	
	public function getName()
	{
		return $this->name;
	}
	
	public function getValue()
	{
		return $this->value;
	}
	
	public function getOperator()
	{
		return $this->operator;
	}
}
