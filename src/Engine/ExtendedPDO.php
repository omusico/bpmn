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

class ExtendedPDO extends \PDO
{
	protected $prefix = '';
	
	public function prepare($statement, $options = NULL)
	{
		$sql = trim(preg_replace("'\s+'", ' ', $statement));
		$sql = str_replace('#__', $this->prefix, $sql);
		
		if($options === NULL)
		{
			return parent::prepare($sql);
		}
		
		return parent::prepare($sql, $options);
	}
}
