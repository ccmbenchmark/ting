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

namespace CCMBenchmark\Ting\Repository;

use CCMBenchmark\Ting\Driver\CacheResult;
use CCMBenchmark\Ting\Driver\ResultInterface;

class Collection implements CollectionInterface
{

    /**
     * @var ResultInterface
     */
    protected $result = null;

    /**
     * @var HydratorInterface|null
     */
    protected $hydrator = null;

    /**
     * @var bool
     */
    protected $fromCache = false;

    /**
     * @param HydratorInterface $hydrator
     */
    public function __construct(HydratorInterface $hydrator = null)
    {
        if ($hydrator === null) {
            $this->hydrator = new HydratorArray();
        } else {
            $this->hydrator = $hydrator;
        }
    }

    /**
     * Fill collection from iterator
     * @param ResultInterface $result
     * @return void
     */
    public function set(ResultInterface $result)
    {
        $this->result = $result;
        $this->hydrator->setResult($result);
    }

    /**
     * @param bool $value
     * @return void
     */
    public function setFromCache($value)
    {
        $this->fromCache = (bool) $value;
    }

    /**
     * @return bool
     */
    public function isFromCache()
    {
        return $this->fromCache;
    }

    /**
     * @return array
     */
    public function toCache()
    {
        $connectionName = null;
        $database = null;
        $data = [];

        if ($this->result !== null) {
            $connectionName = $this->result->getConnectionName();
            $database = $this->result->getDatabase();
            $data = iterator_to_array($this->result);
        }

        return [
            'connection' => $connectionName,
            'database' => $database,
            'data' => $data
        ];
    }

    /**
     * @param array $result
     * @return void
     */
    public function fromCache(array $result)
    {
        $this->setFromCache(true);
        $cacheResult = new CacheResult();
        $cacheResult->setConnectionName($result['connection']);
        $cacheResult->setDatabase($result['database']);
        $cacheResult->setResult(new \ArrayIterator($result['data']));
        $this->set($cacheResult);
    }

    /**
     * @return mixed
     */
    public function first()
    {
        if ($this->result === null) {
            return null;
        }

        $iterator = $this->getIterator();

        /**
         * Some iterator need to be rewind to use current
         */
        $iterator->rewind();

        return $iterator->current();
    }


    /**
     * @return \Generator
     */
    public function getIterator()
    {
        return $this->hydrator->getIterator();
    }

    /**
     * @return int
     */
    public function count()
    {
        return $this->hydrator->count();
    }
}
