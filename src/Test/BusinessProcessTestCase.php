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
use KoolKode\Database\DB;
use KoolKode\Database\PDO\Connection;
use KoolKode\Database\PrefixConnectionDecorator;
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
	protected static $conn;
	
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
		
		if(self::$conn !== NULL)
		{
			return;
		}
		
		$dsn = (string)self::getEnvParam('DB_DSN', 'sqlite::memory:');
		$username = self::getEnvParam('DB_USERNAME', NULL);
		$password = self::getEnvParam('DB_PASSWORD', NULL);
		
		printf("DB: \"%s\"\n", $dsn);
		
		$pdo = new \PDO($dsn, $username, $password);
		$pdo->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);
		
		$conn = new PrefixConnectionDecorator(new Connection($pdo), 'bpm_');
		
		$dir = rtrim(realpath(__DIR__ . '/../Engine'), '/\\');
		$ddl = str_replace('\\', '/', sprintf('%s/ProcessEngine.%s.sql', $dir, $conn->getDriverName()));
		$chunks = explode(';', file_get_contents($ddl));
		
		printf("DDL: \"%s\"\n\n", $ddl);
			
		foreach($chunks as $chunk)
		{
			$sql = trim($chunk);
			
			if($sql === '')
			{
				continue;
			}
			
			$conn->execute($chunk);
		}
		
		self::$conn = $conn;
	}
	
	protected static function getEnvParam($name)
	{
		if(array_key_exists($name, $GLOBALS))
		{
			return $GLOBALS[$name];
		}
		
		if(array_key_exists($name, $_ENV))
		{
			return $_ENV[$name];
		}
		
		if(array_key_exists($name, $_SERVER))
		{
			return $_SERVER[$name];
		}
		
		if(func_num_args() > 1)
		{
			return func_get_arg(1);
		}
		
		throw new \OutOfBoundsException(sprintf('ENV param not found: "%s"', $name));
	}
	
	protected function setUp()
	{
		parent::setUp();
		
		$this->clearTables();
		
		$logger = NULL;
		
		if(!empty($_SERVER['KK_LOG']))
		{
			$stderr = fopen('php://stderr', 'wb');
			
			$logger = new Logger('BPMN');
			$logger->pushHandler(new StreamHandler($stderr));
			$logger->pushProcessor(new PsrLogMessageProcessor());
			
			fwrite($stderr, "\n");
			fwrite($stderr, sprintf("TEST CASE: %s\n", $this->getName()));
			
// 			self::$conn->setDebug(true);
// 			self::$conn->setLogger($logger);
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
		
		$this->processEngine = new ProcessEngine(self::$conn, $this->eventDispatcher, new ExpressionContextFactory());
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
		$this->clearTables();
		
		parent::tearDown();
	}
	
	protected function clearTables()
	{
		static $tables = [
			'#__process_subscription',
			'#__event_subscription',
			'#__user_task',
			'#__execution_variables',
			'#__execution',
			'#__process_definition'
		];
		
		// Need to delete from tabls in correct order to prevent errors due to foreign key constraints.
		foreach($tables as $table)
		{
			self::$conn->execute("DELETE FROM `$table`");
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
