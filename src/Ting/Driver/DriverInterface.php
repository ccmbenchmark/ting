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
     */
    public function connect($hostname, $username, $password, $port): static;

    /**
     * Close the connection to the database
     */
    public function close(): static;

    /**
     * @param string $name
     */
    public function setName($name): static;

    /**
     * @param string $charset
     * @return void
     */
    public function setCharset($charset): void;

    /**
     * @return ($collection is CollectionInterface ? CollectionInterface : bool|array|int|string)
     * @throws QueryException
     */
    public function execute(string $sql, array $params = [], ?CollectionInterface $collection = null): mixed;

    /**
     * @param string $sql
     * @return StatementInterface
     * @throws QueryException
     */
    public function prepare(string $sql): StatementInterface;

    /**
     * @param string $database
     */
    public function setDatabase($database): static;

    /**
     * @param callable $callback
     */
    public function ifIsError(callable $callback): static;

    /**
     * @param callable $callback
     */
    public function ifIsNotConnected(callable $callback): static;

    public function escapeField(mixed $field = null): string;

    public function startTransaction(): void;
    public function rollback(): void;
    public function commit(): void;

    public function getInsertedId(): int;

    /**
     * @return int<0, max>|string
     */
    public function getAffectedRows(): int|string;

    public function setLogger(?DriverLoggerInterface $logger = null): static;

    /**
     * @param array $connectionConfig
     * @param string $database
     */
    public static function getConnectionKey(array $connectionConfig, $database): string;

    /**
     * @param $statement
     * @throws Exception
     */
    public function closeStatement(string $statement): void;

    public function ping(): bool;

    public function setTimezone(?string $timezone = null): void;
}
