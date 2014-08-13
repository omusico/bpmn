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
use KoolKode\BPMN\Engine\BinaryDataParamEncoder;
use KoolKode\BPMN\Engine\IdentifierParamEncoder;
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
		
		if(self::$pdo !== NULL)
		{
			return;
		}
		
		$dsn = (string)self::getEnvParam('DB_DSN', 'sqlite::memory:');
		$username = self::getEnvParam('DB_USERNAME', NULL);
		$password = self::getEnvParam('DB_PASSWORD', NULL);
		
		printf("DB: \"%s\"\n", $dsn);
		
		self::$pdo = new Connection($dsn, $username, $password);
		self::$pdo->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);
		self::$pdo->registerParamEncoder(new BinaryDataParamEncoder());
		self::$pdo->registerParamEncoder(new IdentifierParamEncoder());
		
		if(self::$pdo->isSqlite())
		{
			self::$pdo->exec("PRAGMA foreign_keys = ON");
			
			$db = 'sqlite';
		}
		elseif(self::$pdo->isMySQL())
		{
			$db = 'mysql';
		}
		elseif(self::$pdo->isPostgreSQL())
		{
			$db = 'pgsql';
		}
		else
		{
			throw new \RuntimeException(sprintf('Unsupported database resource: "%s"', $dsn));
		}
		
		$dir = rtrim(realpath(__DIR__ . '/../Engine'), '/\\');
		$ddl = str_replace('\\', '/', sprintf('%s/ProcessEngine.%s.sql', $dir, $db));
		$chunks = explode(';', file_get_contents($ddl));
		
		printf("DDL: \"%s\"\n\n", $ddl);
			
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
			
			self::$pdo->setDebug(true);
			self::$pdo->setLogger($logger);
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
		$this->clearTables();
		
		parent::tearDown();
	}
	
	protected function clearTables()
	{
		static $tables = [
			'bpm_process_subscription',
			'bpm_event_subscription',
			'bpm_user_task',
			'bpm_execution',
			'bpm_process_definition'
		];
		
		if(self::$pdo->isSqlite())
		{
			self::$pdo->exec("PRAGMA foreign_keys = OFF");
	
			try
			{
				foreach($tables as $table)
				{
					self::$pdo->exec("DELETE FROM `#__$table`");
				}
			}
			finally
			{
				self::$pdo->exec("PRAGMA foreign_keys = ON");
			}
		}
		elseif(self::$pdo->isMySQL())
		{
			self::$pdo->exec("SET FOREIGN_KEY_CHECKS=0");
			
			try
			{
				foreach($tables as $table)
				{
					self::$pdo->exec("DELETE FROM `#__$table`");
				}
			}
			finally
			{
				self::$pdo->exec("SET FOREIGN_KEY_CHECKS=1");
			}
		}
		else
		{
			foreach($tables as $table)
			{
				self::$pdo->exec("DELETE FROM `#__$table`");
			}
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
