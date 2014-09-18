<?php

namespace CCMBenchmark\Ting\Driver;

use CCMBenchmark\Ting\Entity\Collection;

interface StatementInterface
{
    public function execute($statement, $params, $paramsOrder, Collection $collection = null);
    public function close();
}
