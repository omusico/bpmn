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

use KoolKode\Util\UUID;

/**
 * Process instances are top-level executions within a BPMN 2.0 process.
 * 
 * @author Martin Schröder
 */
interface ProcessInstanceInterface extends ExecutionInterface
{
	/**
	 * Get the business key of this process instance.
	 * 
	 * @return string Get the business key or NULL when no business key is set.
	 */
	public function getBusinessKey();
	
	/**
	 * Get the unique identifier of the process definition.
	 * 
	 * @return UUID
	 */
	public function getProcessDefinitionId();
}
