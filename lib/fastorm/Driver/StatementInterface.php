<?php

namespace fastorm\Driver;

use fastorm\Entity\Collection;

interface StatementInterface
{
    public function execute($statement, $params, $paramsOrder, Collection $collection = null);
    public function close();
}