<?php

declare(strict_types=1);

namespace Lay\BossFight\util;

class MySQLBinaryStringParser implements BinaryStringParserInstance{

	public function encode(string $string) : string{
		return $string;
	}

	public function decode(string $string) : string{
		return $string;
	}
}