<?php

namespace KoolKode\BPMN;

// FIXME: Adjust to composer packages and decoupling!
use KoolKode\K2\Context\Container;
use KoolKode\K2\Database\PDOConnection;

use KoolKode\Event\EventDispatcher;
use KoolKode\Process\TestEngine;

/**
 * Sets up in in-memory Sqlite databse and a process engine using it.
 * 
 * @author Martin Schr�der
 */
abstract class BusinessProcessTestCase extends \PHPUnit_Framework_TestCase
{
	protected static $logger;
	protected static $pdo;
	
	protected $processEngine;
	protected $repositoryService;
	protected $runtimeService;
	protected $taskService;
	
	protected $container;
	protected $eventDispatcher;
	
	public static function setUpBeforeClass()
	{
		parent::setUpBeforeClass();
		
		self::$logger = new \KoolKode\K2\Log\Logger();
// 		self::$logger->addWriter(new \KoolKode\K2\Log\Writer\StreamLogWriter('php://stdout'));
		
		self::$pdo = new PDOConnection(self::$logger, 'sqlite::memory:');
		self::$pdo->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);
		
		self::$pdo->exec("PRAGMA journal_mode = WAL");
		self::$pdo->exec("PRAGMA locking_mode = EXCLUSIVE");
		self::$pdo->exec("PRAGMA synchronous = NORMAL");
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
		
		$this->container = new Container();
		$this->eventDispatcher = new EventDispatcher($this->container, self::$logger);
		$this->container->bindInstance('KoolKode\K2\Event\EventDispatcherInterface', $this->eventDispatcher);
		
		$engine = new TestEngine($this->container);
		$engine->setLogger(self::$logger);
		$engine->setEventDispatcher($this->eventDispatcher);
		
		$this->processEngine = new ProcessEngine($engine, self::$pdo);
		
		$this->repositoryService = $this->processEngine->getRepositoryService();
		$this->runtimeService = $this->processEngine->getRuntimeService();
		$this->taskService = $this->processEngine->getTaskService();
	
		chdir(dirname((new \ReflectionClass(get_class($this)))->getFileName()));
		
		self::$pdo->beginTransaction();
	}
	
	protected function tearDown()
	{
		parent::tearDown();
		
		if(self::$pdo->inTransaction())
		{
			self::$pdo->rollBack();
		}
	}
}
