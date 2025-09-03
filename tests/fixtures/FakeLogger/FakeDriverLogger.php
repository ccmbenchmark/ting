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

namespace tests\fixtures\FakeLogger;

use CCMBenchmark\Ting\Logger\DriverLoggerInterface;

class FakeDriverLogger implements DriverLoggerInterface
{
    /**
     * Add an opened connection to the list
     */
    public function addConnection(string $name, string $connection, array $connectionConfig): void
    {
    }

    /**
     * Logs a SQL Query
     */
    public function startQuery(string $sql, array $params, string $connection, string $database): void
    {
    }

    /**
     * Log the preparation of a statement
     */
    public function startPrepare(string $sql, string $connection, string $database): void
    {
    }

    /**
     * Log the parameters applied to a statement when executed
     */
    public function startStatementExecute(string $statement, array $params = []): void
    {
    }

    /**
     * Log the end of a query (for timing purposes mainly)
     */
    public function stopQuery(): void
    {
    }

    /**
     * Log the end of the preparation (for timing purposes)
     */
    public function stopPrepare(string $statement): void
    {
    }

    /**
     * Log the end of execution of a prepared statement
     */
    public function stopStatementExecute(string $statement): void
    {
    }
}
