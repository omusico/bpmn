<?php

/*
 * This file is part of KoolKode BPMN.
*
* (c) Martin Schröder <m.schroeder2007@gmail.com>
*
* For the full copyright and license information, please view the LICENSE
* file that was distributed with this source code.
*/

namespace KoolKode\BPMN\Runtime\Behavior;

use KoolKode\BPMN\Engine\AbstractSignalableBehavior;
use KoolKode\BPMN\Engine\VirtualExecution;
use KoolKode\BPMN\Runtime\Command\StartProcessInstanceCommand;
use KoolKode\Expression\ExpressionInterface;

/**
 * Call activity implementation that uses a special process variable to keep track of
 * the calling execution.
 * 
 * @author Martin Schröder
 */
class CallActivityBehavior extends AbstractSignalableBehavior
{
	protected $processDefinitionKey;
	
	protected $name;
	
	protected $inputs;
	
	protected $outputs;
	
	public function __construct($processDefinitionKey, ExpressionInterface $name, array $inputs = [], array $outputs = [])
	{
		$this->processDefinitionKey = (string)$processDefinitionKey;
		$this->name = $name;
		$this->inputs = $inputs;
		$this->outputs = $outputs;
	}
	
	public function executeBehavior(VirtualExecution $execution)
	{
		$variables = [];
		
		foreach($this->inputs as $from => $to)
		{
			$variables[$to] = $execution->getVariable($from, NULL);
		}
		
		$variables['__caller__'] = (string)$execution->getId();
		
		$execution->getEngine()->debug('Starting process {process} from call activity "{task}"', [
			'process' => $this->processDefinitionKey,
			'task' => (string)call_user_func($this->name, $execution->getExpressionContext())
		]);
		
		$definition = $execution->getEngine()->getRepositoryService()->createProcessDefinitionQuery()->processDefinitionKey($this->processDefinitionKey)->findOne();
		$businessKey = $execution->getBusinessKey();
		
		$execution->getEngine()->pushCommand(new StartProcessInstanceCommand(
			$definition, NULL, $businessKey, $variables
		));
		
		$execution->waitForSignal();
	}
	
	public function signalBehavior(VirtualExecution $execution, $signal, array $variables = [])
	{
		$execution->getEngine()->debug('Resuming {execution} at call activity "{task}"', [
			'execution' => (string)$execution,
			'task' => (string)call_user_func($this->name, $execution->getExpressionContext())
		]);
		
		foreach($this->outputs as $from => $to)
		{
			if(array_key_exists($from, $variables))
			{
				$execution->setVariable($to, $variables[$from]);
			}
		}
		
		return $execution->takeAll(NULL, [$execution]);
	}
}
