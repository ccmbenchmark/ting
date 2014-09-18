<?php

namespace CCMBenchmark\Ting\Entity;

use CCMBenchmark\Ting\Query\QueryFactoryInterface;

interface MetadataFactoryInterface
{
    public function __construct(QueryFactoryInterface $queryFactory);
    public function get();
}
