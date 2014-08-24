<?php

/*
 * This file is part of KoolKode BPMN.
 *
 * (c) Martin Schröder <m.schroeder2007@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace KoolKode\BPMN\Engine;

abstract class AbstractQuery
{
	protected function populateMultiProperty(& $prop, $value, callable $converter = NULL)
	{
		if(is_array($value) || $value instanceof \Traversable)
		{
			$prop = [];
				
			foreach($value as $tmp)
			{
				$prop[] = ($converter === NULL) ? (string)$tmp : $converter($tmp);
			}
		}
		else
		{
			$prop = [($converter === NULL) ? (string)$value : $converter($value)];
		}
	
		return $this;
	}
	
	protected function buildPredicate($name, $values, array & $where, array & $params)
	{
		if($values === NULL || (is_array($values) && empty($values)))
		{
			return;
		}
		
		if(count($values) == 1)
		{
			$p1 = 'p' . count($params);
		
			$where[] = sprintf('%s = :%s', $name, $p1);
			$params[$p1] = $values[0];
			
			return;
		}
		
		$ph = [];
	
		foreach($values as $value)
		{
			$p1 = 'p' . count($params);
			
			$ph[] = ":$p1";
			$params[$p1] = $value;
		}
	
		$where[] = sprintf('%s IN (%s)', $name, implode(', ', $ph));
	}
}
