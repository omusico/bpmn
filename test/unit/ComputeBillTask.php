<?php

namespace KoolKode\BPMN;

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
