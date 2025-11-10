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
    protected bool $selectMaster = false;

    protected array $params = [];

    /**
     * @param string $sql
     * @param Connection $connection
     * @param CollectionFactoryInterface $collectionFactory
     */
    public function __construct(
        protected $sql,
        protected Connection $connection,
        protected ?CollectionFactoryInterface $collectionFactory = null
    ) {
    }

    /**
     * Force the query to be executed on the master connection. Applicable only on a reading query.
     */
    public function selectMaster(bool $useMaster): static
    {
        $this->selectMaster = $useMaster;

        return $this;
    }

    /**
     * @param array $params
     * @return $this
     */
    public function setParams(array $params): static
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
    public function query(?CollectionInterface $collection = null): CollectionInterface
    {
        if (!$collection instanceof CollectionInterface) {
            $collection = $this->collectionFactory->get();
        }

        if ($this->selectMaster === true) {
            return $this->connection->master()->execute($this->sql, $this->params, $collection);
        }
        return $this->connection->slave()->execute($this->sql, $this->params, $collection);
    }

    /**
     * Execute a writing query
     * @return mixed
     * @throws Exception
     * @throws QueryException
     */
    public function execute(): mixed
    {
        return $this->connection->master()->execute($this->sql, $this->params);
    }

    /**
     * @throws Exception
     */
    public function getInsertedId(): int
    {
        return $this->connection->master()->getInsertedId();
    }

    /**
     * @throws Exception
     */
    public function getAffectedRows(): int|string
    {
        return $this->connection->master()->getAffectedRows();
    }
}
