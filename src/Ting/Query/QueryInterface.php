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

interface QueryInterface
{
    /**
     * @param string $sql
     * @param Connection $connection
     * @param CollectionFactoryInterface $collectionFactory
     */
    public function __construct($sql, Connection $connection, ?CollectionFactoryInterface $collectionFactory = null);

    /**
     * Execute a reading query (SELECT, SHOW, etc.)
     * @param CollectionInterface<T>|null $collection
     *
     * @return CollectionInterface<T>
     *
     * @template T
     */
    public function query(?CollectionInterface $collection = null);

    /**
     * Execute a writing query (UPDATE, INSERT, etc.)
     * @return mixed
     */
    public function execute();

    /**
     * @param array $params
     * @return void
     */
    public function setParams(array $params);

    /**
     * @return int
     */
    public function getInsertedId();

    /**
     * @return int
     */
    public function getAffectedRows();
}
