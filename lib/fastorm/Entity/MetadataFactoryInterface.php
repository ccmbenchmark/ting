<?php

namespace fastorm\Entity;

use fastorm\Query\QueryFactoryInterface;

interface MetadataFactoryInterface
{
    public function __construct(QueryFactoryInterface $queryFactory);
    public function get();
}
