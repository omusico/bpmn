<?php

/*
 * This file is part of KoolKode BPMN.
 *
 * (c) Martin Schröder <m.schroeder2007@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace KoolKode\BPMN\Test;

use KoolKode\BPMN\Delegate\DelegateTaskRegistry;
use KoolKode\BPMN\Delegate\Event\ServiceTaskExecutedEvent;
use KoolKode\BPMN\Engine\ProcessEngine;
use KoolKode\BPMN\Repository\RepositoryService;
use KoolKode\BPMN\Runtime\Event\MessageThrownEvent;
use KoolKode\BPMN\Runtime\RuntimeService;
use KoolKode\BPMN\Task\TaskService;
use KoolKode\Database\Connection;
use KoolKode\Event\EventDispatcher;
use KoolKode\Expression\ExpressionContextFactory;
use KoolKode\Meta\Info\ReflectionTypeInfo;
use KoolKode\Process\Event\CreateExpressionContextEvent;
use Monolog\Handler\StreamHandler;
use Monolog\Logger;
use Monolog\Processor\PsrLogMessageProcessor;

/**
 * Sets up in in-memory Sqlite databse and a process engine using it.
 * 
 * @author Martin Schröder
 */
abstract class BusinessProcessTestCase extends \PHPUnit_Framework_TestCase
{
	protected static $pdo;
	
	/**
	 * @var EventDispatcher
	 */
	protected $eventDispatcher;
	
	/**
	 * @var ProcessEngine
	 */
	protected $processEngine;
	
	/**
	 * @var DelegateTaskRegistry
	 */
	protected $delegateTasks;
	
	/**
	 * @var RepositoryService
	 */
	protected $repositoryService;
	
	/**
	 * @var RuntimeService
	 */
	protected $runtimeService;
	
	/**
	 * @var TaskService
	 */
	protected $taskService;
	
	protected $messageHandlers;
	
	protected $serviceTaskHandlers;
	
	private $typeInfo;
	
	public static function setUpBeforeClass()
	{
		parent::setUpBeforeClass();
		
		self::$pdo = new Connection('sqlite::memory:');
		self::$pdo->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);
		self::$pdo->exec("PRAGMA foreign_keys = ON");
		
		$chunks = explode(';', file_get_contents(__DIR__ . DIRECTORY_SEPARATOR . 'BusinessProcessTestCase.sqlite.sql'));
			
		foreach($chunks as $chunk)
		{
			$sql = trim($chunk);
			
			if($sql === '')
			{
				continue;
			}
		
			self::$pdo->exec($chunk);
		}
	}
	
	protected function setUp()
	{
		parent::setUp();
		
		$logger = NULL;
		
		if(!empty($_SERVER['KK_LOG']))
		{
			$logger = new Logger('BPMN');
			$logger->pushHandler(new StreamHandler(STDERR));
			$logger->pushProcessor(new PsrLogMessageProcessor());
			
			fwrite(STDERR, "\n");
			fwrite(STDERR, sprintf("TEST CASE: %s\n", $this->getName()));
		}
		
		$this->messageHandlers = [];
		$this->serviceTaskHandlers = [];
		
		$this->eventDispatcher = new EventDispatcher();
		
		// Provide message handler subscriptions.
		$this->eventDispatcher->connect(function(MessageThrownEvent $event) {
			
			$key = $event->execution->getProcessDefinition()->getKey();
			$id = $event->execution->getActivityId();
			
			if(isset($this->messageHandlers[$key][$id]))
			{
				return $this->messageHandlers[$key][$id]($event);
			}
		});
		
		$this->eventDispatcher->connect(function(ServiceTaskExecutedEvent $event) {
			
			$execution = $this->runtimeService->createExecutionQuery()
											  ->executionId($event->execution->getExecutionId())
											  ->findOne();
			
			$key = $execution->getProcessDefinition()->getKey();
			$id = $event->execution->getActivityId();
			
			if(isset($this->serviceTaskHandlers[$key][$id]))
			{
				$this->serviceTaskHandlers[$key][$id]($event->execution);
			}
		});
		
		// Allow for assertions in expressions, e.g. #{ @test.assertEquals(2, processVariable) }
		$this->eventDispatcher->connect(function(CreateExpressionContextEvent $event) {
			$event->access->setVariable('@test', $this);
		});
		
		$this->delegateTasks = new DelegateTaskRegistry();
		
		$this->processEngine = new ProcessEngine(self::$pdo, $this->eventDispatcher, new ExpressionContextFactory());
		$this->processEngine->setDelegateTaskFactory($this->delegateTasks);
		$this->processEngine->setLogger($logger);
		
		$this->repositoryService = $this->processEngine->getRepositoryService();
		$this->runtimeService = $this->processEngine->getRuntimeService();
		$this->taskService = $this->processEngine->getTaskService();
		
		if($this->typeInfo === NULL)
		{
			$this->typeInfo = new ReflectionTypeInfo(new \ReflectionClass(get_class($this)));
		}
		
		foreach($this->typeInfo->getMethods() as $method)
		{
			if(!$method->isPublic() || $method->isStatic())
			{
				continue;
			}
			
			foreach($method->getAnnotations() as $anno)
			{
				if($anno instanceof MessageHandler)
				{
					$this->messageHandlers[$anno->processKey][$anno->value] = [$this, $method->getName()];
				}
				
				if($anno instanceof ServiceTaskHandler)
				{
					$this->serviceTaskHandlers[$anno->processKey][$anno->value] = [$this, $method->getName()];
				}
			}
		}
	}
	
	protected function tearDown()
	{
		static $tables = [
			'bpm_process_definition',
			'bpm_process_subscription',
			'bpm_execution',
			'bpm_event_subscription',
			'bpm_user_task'
		];
		
		parent::tearDown();
		
		self::$pdo->exec("PRAGMA foreign_keys = OFF");
		
		foreach($tables as $table)
		{
			self::$pdo->exec("DELETE FROM $table");
		}
	}
	
	protected function deployFile($file)
	{
		if(!preg_match("'^(?:(?:[a-z]:)|(/+)|([^:]+://))'i", $file))
		{
			$file = dirname((new \ReflectionClass(get_class($this)))->getFileName()) . DIRECTORY_SEPARATOR . $file;
		}
		
		return $this->repositoryService->deployDiagram($file);
	}
	
	protected function registerMessageHandler($processDefinitionKey, $nodeId, callable $handler)
	{
		$args = array_slice(func_get_args(), 3);
		
		$this->messageHandlers[(string)$processDefinitionKey][(string)$nodeId] = function($event) use($handler, $args) {
			return call_user_func_array($handler, array_merge([$event], $args));
		};
	}
	
	protected function registerServiceTaskHandler($processDefinitionKey, $activityId, callable $handler)
	{
		$this->serviceTaskHandlers[(string)$processDefinitionKey][(string)$activityId] = $handler;
	}
}
