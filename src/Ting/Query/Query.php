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

namespace CCMBenchmark\Ting\Query;

use CCMBenchmark\Ting\Connection;
use CCMBenchmark\Ting\Exception;
use CCMBenchmark\Ting\Repository\CollectionInterface;
use CCMBenchmark\Ting\Repository\CollectionFactoryInterface;

class Query implements QueryInterface
{

    /**
     * @var string|null
     */
    protected $sql = null;

    /**
     * @var Connection|null
     */
    protected $connection = null;

    /**
     * @var CollectionFactoryInterface|null
     */
    protected $collectionFactory = null;

    /**
     * @var bool
     */
    protected $selectMaster = false;

    /**
     * @var array
     */
    protected $params = [];

    /**
     * @param string $sql
     * @param Connection $connection
     * @param CollectionFactoryInterface $collectionFactory
     */
    public function __construct($sql, Connection $connection, CollectionFactoryInterface $collectionFactory = null)
    {
        $this->sql               = $sql;
        $this->connection        = $connection;
        $this->collectionFactory = $collectionFactory;
        return $this;
    }

    /**
     * Force the query to be executed on the master connection. Applicable only on a reading query.
     * @param bool $value
     * @return void
     */
    public function selectMaster($value)
    {
        $this->selectMaster = (bool) $value;
    }

    /**
     * @param array $params
     * @return $this
     */
    public function setParams(array $params)
    {
        $this->params = $params;

        return $this;
    }

    /**
     * Execute a reading query
     * @param CollectionInterface $collection
     * @return CollectionInterface
     * @throws Exception
     * @throws QueryException
     */
    public function query(CollectionInterface $collection = null)
    {
        if ($collection === null) {
            $collection = $this->collectionFactory->get();
        }

        if ($this->selectMaster === true) {
            return $this->connection->master()->execute($this->sql, $this->params, $collection);
        } else {
            return $this->connection->slave()->execute($this->sql, $this->params, $collection);
        }
    }

    /**
     * Execute a writing query
     * @return mixed
     * @throws Exception
     * @throws QueryException
     */
    public function execute()
    {
        return $this->connection->master()->execute($this->sql, $this->params);
    }

    /**
     * @return int
     * @throws Exception
     */
    public function getInsertId()
    {
        return $this->connection->master()->getInsertId();
    }

    /**
     * @return int
     * @throws Exception
     */
    public function getAffectedRows()
    {
        return $this->connection->master()->getAffectedRows();
    }
}
