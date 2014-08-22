<?php

/*
 * This file is part of KoolKode BPMN.
 *
 * (c) Martin Schröder <m.schroeder2007@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace KoolKode\BPMN;

use KoolKode\BPMN\Delegate\DelegateExecutionInterface;
use KoolKode\BPMN\Delegate\DelegateTaskInterface;

class ComputeBillTask implements DelegateTaskInterface
{
	public $result = 0;
	
	public function execute(DelegateExecutionInterface $execution)
	{
		$amount = (int)$execution->getVariable('amount');
		$discount = (int)$execution->getVariable('discount', 0);
		
		$this->result = $amount - $discount;
	}
}
