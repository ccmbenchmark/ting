<?php
/***********************************************************************
 *
 * Ting - PHP Datamapper
 * ==========================================
 *
 * Copyright (C) 2014 CCM Benchmark Group. (http://www.ccmbenchmark.com)
 *
 ***********************************************************************
 *
 * Licensed under the Apache License, Version 2.0 (the "License"); you
 * may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 *
 *     http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or
 * implied. See the License for the specific language governing
 * permissions and limitations under the License.
 *
 **********************************************************************/

namespace tests\fixtures\FakeDriver;

use CCMBenchmark\Ting\Driver\DriverInterface;
use CCMBenchmark\Ting\Driver\StatementInterface;
use CCMBenchmark\Ting\Query\QueryAbstract;
use CCMBenchmark\Ting\Repository\Collection;
use CCMBenchmark\Ting\Repository\CollectionInterface;

class Driver implements DriverInterface
{

    public static function forConnectionKey($connectionConfig, $database, \Closure $callback)
    {
        $callback(
            $connectionConfig['host'] . '|' .
            $connectionConfig['port'] . '|' .
            $connectionConfig['user'] . '|' .
            $connectionConfig['password']
        );
    }

    public function connect($hostname, $username, $password, $port)
    {

    }

    public function execute(
        $sql,
        $params = array(),
        $queryType = QueryAbstract::TYPE_RESULT,
        CollectionInterface $collection = null
    ) {

    }

    public function prepare(
        $sql,
        \Closure $callback,
        $queryType = QueryAbstract::TYPE_RESULT,
        StatementInterface $statement = null
    ) {

    }

    public function setDatabase($database)
    {
        $this->database = $database;
    }

    public function ifIsError(\Closure $callback)
    {

    }

    public function ifIsNotConnected(\Closure $callback)
    {

    }

    public function escapeFields($fields, \Closure $callback)
    {

    }

    public function startTransaction()
    {

    }

    public function rollback()
    {

    }

    public function commit()
    {

    }
}
