<?php

declare(strict_types=1);

namespace Lay\BossFight\util;

interface BinaryStringParserInstance{

	public function encode(string $string) : string;

	public function decode(string $string) : string;
}