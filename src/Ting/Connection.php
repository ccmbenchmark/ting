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

use CCMBenchmark\Ting\Repository\CollectionInterface;

class Connection
{

    /**
     * @var ConnectionPool|null
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
     * @param ConnectionPool $connectionPool
     * @param string $name
     * @param string $database
     */
    public function __construct(ConnectionPool $connectionPool, $name, $database)
    {
        $this->connectionPool = $connectionPool;
        $this->name           = $name;
        $this->database       = $database;
    }

    /**
     * @param callable $callback
     */
    public function forCacheKey(callable $callback)
    {
        $callback($this->name . '|' . $this->database);
    }

    /**
     * @param string $sql
     * @param array $params
     * @param CollectionInterface $collection
     * @return mixed
     */
    public function onMasterDoExecute($sql, $params, CollectionInterface $collection = null)
    {
        return $this
            ->connectionPool
            ->onMasterDoExecute($this->name, $this->database, $sql, $params, $collection);
    }

    /**
     * @param string $sql
     * @param array $params
     * @param CollectionInterface $collection
     * @return mixed
     */
    public function onSlaveDoExecute($sql, $params, CollectionInterface $collection = null)
    {
        return $this
            ->connectionPool
            ->onSlaveDoExecute($this->name, $this->database, $sql, $params, $collection);
    }

    /**
     * @param $sql
     * @return \CCMBenchmark\Ting\Driver\StatementInterface
     */
    public function onMasterDoPrepare($sql)
    {
         return $this
            ->connectionPool
            ->onMasterDoPrepare($this->name, $this->database, $sql);
    }

    /**
     * @param string $sql
     * @return \CCMBenchmark\Ting\Driver\StatementInterface
     */
    public function onSlaveDoPrepare($sql)
    {
        return $this
            ->connectionPool
            ->onSlaveDoPrepare($this->name, $this->database, $sql);
    }

    /**
     * @return void
     */
    public function onMasterStartTransaction()
    {
        $this->connectionPool->onMasterStartTransaction($this->name, $this->database);
    }

    /**
     * @return void
     */
    public function onMasterRollback()
    {
        $this->connectionPool->onMasterRollback($this->name, $this->database);
    }

    /**
     * @return void
     */
    public function onMasterCommit()
    {
        $this->connectionPool->onMasterCommit($this->name, $this->database);
    }

    /**
     * @return int
     */
    public function onMasterDoGetInsertId()
    {
        return (int) $this->connectionPool->onMasterDoGetInsertId($this->name, $this->database);
    }

    /**
     * @return int
     */
    public function onSlaveDoGetInsertId()
    {
        return $this->connectionPool->onSlaveDoGetInsertId($this->name, $this->database);
    }

    /**
     * @return int
     */
    public function onMasterDoGetAffectedRows()
    {
        return $this->connectionPool->onMasterDoGetAffectedRows($this->name, $this->database);
    }

    /**
     * @return int
     */
    public function onSlaveDoGetAffectedRows()
    {
        return $this->connectionPool->onSlaveDoGetAffectedRows($this->name, $this->database);
    }
}
