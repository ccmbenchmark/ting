<?php

namespace CCMBenchmark\Ting\Schema;

#[\Attribute(\Attribute::TARGET_CLASS)]
readonly class Table
{
    public function __construct(public string $name, public string $connection, public string $database, public string $repository)
    {
        
    }
}