<?php

namespace KoolKode\BPMN\Engine;

interface ProcessEngineInterface
{
	public function getRepositoryService();
	
	public function getRuntimeService();
	
	public function getTaskService();
}
