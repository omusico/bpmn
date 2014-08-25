<?php

/*
 * This file is part of KoolKode BPMN.
 *
 * (c) Martin Schröder <m.schroeder2007@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace KoolKode\BPMN\Repository;

use KoolKode\Stream\ResourceStream;
use KoolKode\Stream\StreamInterface;
use KoolKode\Stream\StringStream;
use KoolKode\Stream\UrlStream;

/**
 * Builds a deployment from any number of resources.
 * 
 * @author Martin Schröder
 */
class DeploymentBuilder implements \Countable, \IteratorAggregate
{
	protected $name;
	
	protected $fileExtensions = ['bpmn'];
	
	protected $resources = [];
	
	public function __construct($name)
	{
		$this->name = $name;
	}
	
	public function count()
	{
		return count($this->resources);
	}
	
	public function getIterator()
	{
		return new \ArrayIterator($this->resources);
	}
	
	public function getName()
	{
		return $this->name;
	}
	
	/**
	 * Add a file extension that shoul be parsed for BPMN 2.0 process definitions.
	 * 
	 * The deployment mechanism will parse ".bpmn" files by default.
	 * 
	 * @param string$extension
	 * @return DeploymentBuilder
	 */
	public function addExtensions($extension)
	{
		$this->fileExtensions = array_unique(array_merge($this->fileExtensions, [strtolower($extension)]));
		
		return $this;
	}
	
	/**
	 * Check if the given file will be parsed for BPMN 2.0 process definitions.
	 * 
	 * @param string $name
	 * @return boolean
	 */
	public function isProcessResource($name)
	{
		return in_array(strtolower(pathinfo($name, PATHINFO_EXTENSION)), $this->fileExtensions);
	}
	
	/**
	 * Add a resource to the deployment.
	 * 
	 * @param string $name Local path and filename of the resource within the deployment.
	 * @param mixed $resource Deployable resource (file), that can be loaded using a stream.
	 * @return DeploymentBuilder
	 */
	public function addResource($name, $resource)
	{
		if($resource instanceof StreamInterface)
		{
			$in = $resource;
		}
		elseif(is_resource($resource))
		{
			$in = new ResourceStream($resource);
		}
		elseif(preg_match("'^/|(?:[^:\\\\/]+://)|(?:[a-z]:[\\\\/])'i", $resource))
		{
			$in = new UrlStream((string)$resource, 'rb');
		}
		else
		{
			$in = new StringStream($resource);
		}
		
		$this->resources[trim(str_replace('\\', '/', $name), '/')] = $in;
		
		return $this;
	}
}
