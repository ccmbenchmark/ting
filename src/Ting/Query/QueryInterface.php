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

use CCMBenchmark\Ting\ConnectionPoolInterface;
use CCMBenchmark\Ting\Driver\DriverInterface;
use CCMBenchmark\Ting\Repository\CollectionInterface;
use CCMBenchmark\Ting\Repository\Metadata;

interface QueryInterface
{
    public function __construct($sql, array $params = null);

    /**
     * @param array $params
     * @return $this
     * @throws \InvalidArgumentException
     */
    public function setParams(array $params);

    /**
     * @param DriverInterface $driver
     * @return $this
     */
    public function setDriver(DriverInterface $driver);

    /**
     * @param Metadata $metadata
     * @param ConnectionPoolInterface $connectionPool
     * @param CollectionInterface $collection
     * @param null $connectionType
     * @return mixed
     */
    public function execute(
        Metadata $metadata,
        ConnectionPoolInterface $connectionPool,
        CollectionInterface $collection = null,
        $connectionType = null
    );

    public function executeCallbackWithConnectionType(\Closure $callback);
}
