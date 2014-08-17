<?php

/*
 * This file is part of KoolKode BPMN.
 *
 * (c) Martin Schröder <m.schroeder2007@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace KoolKode\BPMN\Engine;

use KoolKode\Database\ConnectionInterface;
use KoolKode\Database\DB;
use KoolKode\Database\ParamEncoderInterface;
use KoolKode\Util\UUID;

/**
 * Encodes UUID identifiers into binary representations.
 * 
 * @author Martin Schröder
 */
class IdentifierParamEncoder implements ParamEncoderInterface
{
	public function encodeParam(ConnectionInterface $conn, $param, & $isEncoded)
	{
		if($param instanceof UUID)
		{
			$isEncoded = true;
			
			if($conn->getDriverName() == DB::DRIVER_POSTGRESQL)
			{
				return str_replace('-', '', (string)$param);
			}
			
			return $param->toBinary();
		}
	}
}
