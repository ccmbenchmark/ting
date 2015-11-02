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

namespace CCMBenchmark\Ting\Driver;

use CCMBenchmark\Ting\Logger\DriverLoggerInterface;
use CCMBenchmark\Ting\Repository\CollectionInterface;

interface DriverInterface
{

    /**
     * @param string $hostname
     * @param string $username
     * @param string $password
     * @param int $port
     * @return $this
     */
    public function connect($hostname, $username, $password, $port);

    /**
     * Close the connection to the database
     * @return $this
     */
    public function close();

    /**
     * @param $name
     * @return $this
     */
    public function setName($name);

    /**
     * @param string $charset
     * @return void
     */
    public function setCharset($charset);

    /**
     * @param string $sql
     * @param array $params
     * @param CollectionInterface $collection
     * @return mixed
     */
    public function execute($sql, array $params = [], CollectionInterface $collection = null);

    /**
     * @param string $sql
     * @return StatementInterface
     */
    public function prepare($sql);

    /**
     * @param string $database
     */
    public function setDatabase($database);

    /**
     * @param callable $callback
     */
    public function ifIsError(callable $callback);

    /**
     * @param callable $callback
     */
    public function ifIsNotConnected(callable $callback);

    /**
     * @param $field
     * @return string
     */
    public function escapeField($field);

    public function startTransaction();
    public function rollback();
    public function commit();

    /**
     * @return int
     */
    public function getInsertId();

    /**
     * @return int
     */
    public function getAffectedRows();

    public function setLogger(DriverLoggerInterface $logger = null);

    /**
     * @param array $connectionConfig
     * @param string $database
     * @return string
     */
    public static function getConnectionKey(array $connectionConfig, $database);

    /**
     * @param $statement
     * @throws Exception
     */
    public function closeStatement($statement);
}
