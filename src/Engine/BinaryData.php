<?php

namespace KoolKode\BPMN\Engine;

/*
 * This file is part of KoolKode BPMN.
 *
 * (c) Martin Schröder <m.schroeder2007@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

class BinaryData
{
	const TYPE_RAW = 1;
	
	const TYPE_HEX = 2;
	
	protected $data;
	
	protected $level;
	
	public function __construct($data, $level = 1)
	{
		$this->data = (string)$data;
		$this->level = (int)$level;
	}
	
	public function __toString()
	{
		return $this->data;
	}
	
	public function encode()
	{
		return $this->data;
		// FIXME: Compress data...
// 		return gzcompress($this->data, $this->level);
	}
	
	public static function decode($input)
	{
		if(is_resource($input))
		{
			$input = stream_get_contents($input);
		}
		
		if($input === NULL || '' === (string)$input)
		{
			return NULL;
		}
		return $input;
		// FIXME: Uncompress data...
		return gzuncompress($input);
	}
}