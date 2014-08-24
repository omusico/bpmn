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

/**
 * Provides constraints based on process / execution variable values.
 * 
 * @author Martin Schröder
 */
trait VariableQueryTrait
{
	protected $variableValues = [];
	
	public function variableValue(QueryVariableValue $value)
	{
		$this->variableValues[] = $value;
		
		return $this;
	}
	
	public function variableValueEqualTo($name, $value)
	{
		$this->variableValues[] = new QueryVariableValue($name, $value, '=');
	
		return $this;
	}
	
	public function variableValueNotEqualTo($name, $value)
	{
		$this->variableValues[] = new QueryVariableValue($name, $value, '<>');
	
		return $this;
	}
	
	public function variableValueLike($name, $value)
	{
		$this->variableValues[] = new QueryVariableValue($name, $value, 'LIKE');
	
		return $this;
	}
	
	public function variableValueNotLike($name, $value)
	{
		$this->variableValues[] = new QueryVariableValue($name, $value, 'NOT LIKE');
	
		return $this;
	}
	
	public function variableValueLessThan($name, $value)
	{
		$this->variableValues[] = new QueryVariableValue($name, $value, '<');
	
		return $this;
	}
	
	public function variableValueLessThanOrEqualTo($name, $value)
	{
		$this->variableValues[] = new QueryVariableValue($name, $value, '<=');
	
		return $this;
	}
	
	public function variableValueGreaterThan($name, $value)
	{
		$this->variableValues[] = new QueryVariableValue($name, $value, '>');
	
		return $this;
	}
	
	public function variableValueGreaterThanOrEqualTo($name, $value)
	{
		$this->variableValues[] = new QueryVariableValue($name, $value, '>=');
	
		return $this;
	}
}
