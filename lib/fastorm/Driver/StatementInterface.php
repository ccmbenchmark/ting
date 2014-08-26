<?php

namespace fastorm\Driver;

use fastorm\Entity\Collection;

interface StatementInterface
{
    const TYPE_RESULT   = 1;
    const TYPE_AFFECTED = 2;
    const TYPE_INSERT   = 3;

    public function execute($statement, $params, $paramsOrder, Collection $collection = null);
    public function close();
}
