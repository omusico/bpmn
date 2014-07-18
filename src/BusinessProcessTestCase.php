<?php

namespace KoolKode\BPMN;

use KoolKode\Event\EventDispatcher;
use KoolKode\Expression\ExpressionContextFactory;
use KoolKode\Process\ExecutionExpressionResolver;
use KoolKode\Process\TestEngine;

/**
 * Sets up in in-memory Sqlite databse and a process engine using it.
 * 
 * @author Martin Schröder
 */
abstract class BusinessProcessTestCase extends \PHPUnit_Framework_TestCase
{
	protected static $pdo;
	
	protected $processEngine;
	protected $repositoryService;
	protected $runtimeService;
	protected $taskService;
	
	protected $eventDispatcher;
	protected $delegateTasks;
	
	public static function setUpBeforeClass()
	{
		parent::setUpBeforeClass();
		
		self::$pdo = new ExtendedPDO('sqlite::memory:');
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
		
		$this->eventDispatcher = new EventDispatcher();
		
		$factory = new ExpressionContextFactory();
		$factory->getResolvers()->registerResolver(new ExecutionExpressionResolver());
		
		$engine = new TestEngine($this->eventDispatcher, $factory);
		
		$this->delegateTasks = new DelegateTaskRegistry();
		
		$this->processEngine = new ProcessEngine($engine, self::$pdo);
		$this->processEngine->setDelegateTaskFactory($this->delegateTasks);
		
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
