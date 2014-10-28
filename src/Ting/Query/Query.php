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
     * @param string $sql
     * @param Connection $connection
     * @param CollectionFactoryInterface $collectionFactory
     */
    public function __construct($sql, Connection $connection, CollectionFactoryInterface $collectionFactory)
    {
        $this->sql               = $sql;
        $this->connection        = $connection;
        $this->collectionFactory = $collectionFactory;
        return $this;
    }

    /**
     * @param bool $value
     * @return void
     */
    public function selectMaster($value)
    {
        $this->selectMaster = (bool) $value;
    }

    /**
     * @param array $params
     * @param CollectionInterface $collection
     * @return void
     */
    protected function executeOnConnection(array $params, CollectionInterface $collection = null)
    {
        if ($this->selectMaster === true) {
            $this->connection->onMasterDoExecute($this->sql, $params, $collection);
        } else {
            $this->connection->onSlaveDoExecute($this->sql, $params, $collection);
        }
    }

    /**
     * @param array $params
     * @param CollectionInterface $collection
     * @return CollectionInterface
     */
    public function query(array $params, CollectionInterface $collection = null)
    {
        if ($collection === null) {
            $collection = $this->collectionFactory->get();
        }

        $this->executeOnConnection($params, $collection);

        return $collection;
    }

    /**
     * @param array $params
     * @return mixed
     */
    public function execute(array $params)
    {
        return $this->connection->onMasterDoExecute($this->sql, $params);
    }

    /**
     * @return int
     */
    public function getInsertId()
    {
        if ($this->selectMaster === true) {
            return $this->connection->onMasterDoGetInsertId();
        } else {
            return $this->connection->onSlaveDoGetInsertId();
        }
    }

    /**
     * @return int
     */
    public function getAffectedRows()
    {
        if ($this->selectMaster === true) {
            return $this->connection->onMasterDoGetAffectedRows();
        } else {
            return $this->connection->onSlaveDoGetAffectedRows();
        }
    }
}
