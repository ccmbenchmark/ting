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

namespace tests\fixtures\FaultyDriver;

use CCMBenchmark\Ting\Driver\DriverInterface;
use CCMBenchmark\Ting\Driver\Exception;
use CCMBenchmark\Ting\Driver\StatementInterface;
use CCMBenchmark\Ting\Logger\DriverLoggerInterface;
use CCMBenchmark\Ting\Repository\CollectionInterface;

class Driver implements DriverInterface
{
    private $name;
    /** @var string|null */
    private $timezone = null;

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

    public function close()
    {

    }

    public function setName($name)
    {
        $this->name = (string) $name;
    }

    public function getName()
    {
        return $this->name;
    }

    public function setCharset($charset)
    {

    }

    public function setLogger(DriverLoggerInterface $logger = null)
    {

    }


    /**
     * @param string $sql
     * @param array $params
     * @param CollectionInterface $collection
     * @return mixed
     */
    public function execute($sql, array $params = array(), CollectionInterface $collection = null)
    {

    }

    /**
     * @param string $sql
     * @return StatementInterface
     */
    public function prepare($sql)
    {

    }

    /**
     * @param $field
     * @return string
     */
    public function escapeField($field)
    {

    }

    /**
     * @return int
     */
    public function getInsertId()
    {

    }

    /**
     * @return int
     */
    public function getAffectedRows()
    {

    }

    /**
     * @param array $connectionConfig
     * @param string $database
     * @return string
     */
    public static function getConnectionKey(array $connectionConfig, $database)
    {
        return md5(var_export($connectionConfig, true) . $database);
    }

    /**
     * @param string $database
     */
    public function setDatabase($database)
    {

    }

    /**
     * @param callable $callback
     */
    public function ifIsError(callable $callback)
    {

    }

    /**
     * @param callable $callback
     */
    public function ifIsNotConnected(callable $callback)
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

    /**
     * @param $statement
     * @throws Exception
     */
    public function closeStatement($statement)
    {

    }
}
