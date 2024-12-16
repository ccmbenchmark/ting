<?php

namespace CCMBenchmark\Ting\Schema;

use Attribute;

#[Attribute(Attribute::TARGET_PROPERTY)]
class CharColumn extends Column
{
    public function __construct(public string $type, public int $length)
    {
        
    }
}