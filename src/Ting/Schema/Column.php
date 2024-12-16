<?php

namespace CCMBenchmark\Ting\Schema;

#[\Attribute(\Attribute::TARGET_PROPERTY)]
readonly class Column
{
    public function __construct($autoIncrement = false, $primary = false)
    {
        
    }
}