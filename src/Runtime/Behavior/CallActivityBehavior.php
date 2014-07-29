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
		$context = $execution->getExpressionContext();
		$definition = $execution->getEngine()->getRepositoryService()->createProcessDefinitionQuery()->processDefinitionKey($this->processDefinitionKey)->findOne();
		$businessKey = $execution->getBusinessKey();
		
		$execution->getEngine()->debug('Starting process {process} from call activity "{task}"', [
			'process' => $this->processDefinitionKey,
			'task' => (string)call_user_func($this->name, $context)
		]);
		
		$start = $definition->getModel()->findInitialNodes();
		
		if(count($start) !== 1)
		{
			throw new \RuntimeException(sprintf('Missing single non start event in process %s', $definition->getKey()));
		}
		
		$sub = $execution->createNestedExecution($definition->getModel(), true);
		
		foreach($this->inputs as $target => $source)
		{
			if($source instanceof ExpressionInterface)
			{
				$sub->setVariable($target, $source($context));
			}
			elseif($execution->hasVariable($source))
			{
				$sub->setVariable($target, $execution->getVariable($source));
			}
		}
		
		$execution->waitForSignal();
		
		$sub->execute(array_shift($start));
	}
	
	public function signalBehavior(VirtualExecution $execution, $signal, array $variables = [])
	{
		$sub = $variables['@execution'];
		
		if(!$sub instanceof VirtualExecution)
		{
			throw new \RuntimeException(sprintf('Missing nested execution being signaled'));
		}
		
		$context = $execution->getEngine()->getExpressionContextFactory()->createContext($sub);
		
		foreach($this->outputs as $target => $source)
		{
			if($source instanceof ExpressionInterface)
			{
				$execution->setVariable($target, $source($context));
			}
			elseif($sub->hasVariable($source))
			{
				$execution->setVariable($target, $sub->getVariable($source));
			}
		}
		
		$execution->getEngine()->debug('Resuming {execution} at call activity "{task}"', [
			'execution' => (string)$execution,
			'task' => (string)call_user_func($this->name, $execution->getExpressionContext())
		]);
		
		return $execution->takeAll(NULL, [$execution]);
	}
}
