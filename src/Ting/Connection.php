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

class Connection
{
    /**
     * @var ConnectionPoolInterface|null
     */
    protected $connectionPool = null;

    /**
     * @var null|string
     */
    protected $database = null;

    /**
     * @var null|string
     */
    protected $name = null;

    /**
     * @param ConnectionPoolInterface $connectionPool
     * @param string $name
     * @param string $database
     *
     * @internal
     */
    public function __construct(ConnectionPoolInterface $connectionPool, ?string $name, ?string $database)
    {
        if ($name === null || $database === null) {
            throw new \RuntimeException('Name and databases cannot be null on connection');
        }
        $this->connectionPool = $connectionPool;
        $this->name           = $name;
        $this->database       = $database;
    }

    /**
     * Return the master connection
     * @throws Exception
     * @return Driver\DriverInterface
     */
    public function master()
    {
        return $this->connectionPool->master($this->name, $this->database);
    }

    /**
     * Return a slave connection
     * @return Driver\DriverInterface
     * @throws Exception
     */
    public function slave()
    {
        return $this->connectionPool->slave($this->name, $this->database);
    }

    /**
     * Start a transaction against the master connection
     * @return mixed
     * @throws Exception
     */
    public function startTransaction()
    {
        return $this->master()->startTransaction();
    }

    /**
     * Commit the opened transaction on the master connection
     * @return mixed
     * @throws Exception
     */
    public function commit()
    {
        return $this->master()->commit();
    }

    /**
     * Rollback the opened transaction on the master connection
     * @return mixed
     * @throws Exception
     */
    public function rollback()
    {
        return $this->master()->rollback();
    }
}
