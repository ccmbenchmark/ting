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
use CCMBenchmark\Ting\Repository\Collection;

abstract class QueryAbstract
{
    const TYPE_RESULT   = 1;
    const TYPE_AFFECTED = 2;
    const TYPE_INSERT   = 3;

    protected $sql       = '';
    protected $params    = array();
    protected $queryType = self::TYPE_RESULT;

    /**
     * @var DriverInterface $driver
     */
    protected $driver = null;

    public function __construct($sql, array $params = null)
    {
        $this->sql = $sql;

        if ($params !== null) {
            $this->params = $params;
        }

        $this->setQueryType();

        return $this;
    }

    /**
     * @param array $params
     * @return $this
     * @throws \InvalidArgumentException
     */
    public function setParams(array $params)
    {
        $this->params = $params;

        return $this;
    }

    /**
     * @param DriverInterface $driver
     * @return $this
     */
    public function setDriver(DriverInterface $driver)
    {
        $this->driver = $driver;

        return $this;
    }

    /**
     * @param \CCMBenchmark\Ting\Repository\Collection $collection
     * @return mixed
     * @throws QueryException
     */
    abstract public function execute(Collection $collection = null);

    final private function setQueryType()
    {
        $queryType = self::TYPE_RESULT;
        $sqlCompare = trim(strtoupper($this->sql));
        /* @todo We REALLY need to do this better :  we don't like playing riddle */
        if (strpos($sqlCompare, 'UPDATE') === 0 || strpos($sqlCompare, 'DELETE') === 0) {
            $queryType = self::TYPE_AFFECTED;
        } elseif (strpos($sqlCompare, 'INSERT') === 0 || strpos($sqlCompare, 'REPLACE' === 0)) {
            $queryType = self::TYPE_INSERT;
        }
        $this->queryType = $queryType;
    }

    public function executeCallbackWithConnectionType(\Closure $callback)
    {
        if (in_array($this->queryType, [self::TYPE_AFFECTED, self::TYPE_INSERT])) {
            $callback(ConnectionPoolInterface::CONNECTION_MASTER);
        } else {
            $callback(ConnectionPoolInterface::CONNECTION_SLAVE);
        }
    }
}
