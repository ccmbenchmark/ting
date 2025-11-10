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

namespace CCMBenchmark\Ting;

use RuntimeException;

class Connection
{
    /**
     * @internal
     */
    public function __construct(
        protected ConnectionPoolInterface $connectionPool,
        protected string $name,
        protected string $database
    ) {
        if ($name === '' || $database === '') {
            throw new RuntimeException('Name and databases cannot be empty on connection');
        }
    }

    /**
     * Return the master connection
     * @throws Exception
     * @return Driver\DriverInterface
     */
    public function master(): Driver\DriverInterface
    {
        return $this->connectionPool->master($this->name, $this->database);
    }

    /**
     * Return a slave connection
     * @return Driver\DriverInterface
     * @throws Exception
     */
    public function slave(): Driver\DriverInterface
    {
        return $this->connectionPool->slave($this->name, $this->database);
    }

    /**
     * Start a transaction against the master connection
     * @throws Exception
     */
    public function startTransaction(): void
    {
        $this->master()->startTransaction();
    }

    /**
     * Commit the opened transaction on the master connection
     * @throws Exception
     */
    public function commit(): void
    {
        $this->master()->commit();
    }

    /**
     * Rollback the opened transaction on the master connection
     * @throws Exception
     */
    public function rollback(): void
    {
        $this->master()->rollback();
    }
}
