<?php

declare(strict_types=1);

namespace Lay\BossFight\util;

use RuntimeException;

final class BinaryStringParser{

	public static function fromDatabase(string $type) : BinaryStringParserInstance{
		return match($type){
			"mysql" => new MySQLBinaryStringParser(),
			"sqlite" => new SQLiteBinaryStringParser(),
			default => throw new RuntimeException("Unsupported database: {$type}")
		};
	}
}