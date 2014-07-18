<?php

/*
 * This file is part of KoolKode BPMN.
*
* (c) Martin Schröder <m.schroeder2007@gmail.com>
*
* For the full copyright and license information, please view the LICENSE
* file that was distributed with this source code.
*/

namespace KoolKode\BPMN\Command;

use KoolKode\BPMN\CommandInterface;

abstract class AbstractCommand implements CommandInterface
{
	public function getPriority()
	{
		return self::PRIORITY_DEFAULT;
	}
}
