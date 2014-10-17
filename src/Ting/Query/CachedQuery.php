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


use CCMBenchmark\Ting\Cache\CacheInterface;
use CCMBenchmark\Ting\Driver\DriverInterface;
use CCMBenchmark\Ting\Repository\CollectionInterface;

class CachedQuery implements QueryInterface
{
    protected $sql      = null;
    protected $params   = array();
    protected $ttl      = 0;
    /**
     * @var CacheInterface
     */
    protected $cache    = null;
    /**
     * @var Query
     */
    protected $query    = null;

    /**
     * @var int version number used in keys.
     */
    protected $version  = 0;

    public function __construct($sql, array $params = null)
    {
        $this->query = new Query($sql, $params);
    }

    public function setCacheDriver(CacheInterface $cacheDriver)
    {
        $this->cache = $cacheDriver;

        return $this;
    }

    public function setTtl($ttl)
    {
        $this->ttl = (int)$ttl;

        return $this;
    }

    public function setVersion($version)
    {
        $this->version = $version;

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
        $this->query->setParams($params);

        return $this;
    }

    /**
     * @param DriverInterface $driver
     * @return $this
     */
    public function setDriver(DriverInterface $driver)
    {
        $this->query->setDriver($driver);

        return $this;
    }

    /**
     * @param CollectionInterface $collection
     * @return mixed
     * @throws QueryException
     */
    public function execute(CollectionInterface $collection = null)
    {
        ksort($this->params);
        // TODO : add connectionName before hashing
        $key = sha1($this->sql . serialize($this->params)) . '-' . $this->version;

        if ($values = $this->cache->get($key)) {
            echo 'Cache'."\n";
            $collection->fromArray($values);
        } else {
            echo 'Pas cache'."\n";
            $this->query->execute($collection);
            $this->cache->store($key, $collection->toArray(), $this->ttl);
        }

        return $collection;
    }

    public function executeCallbackWithConnectionType(\Closure $callback)
    {
        $this->query->executeCallbackWithConnectionType($callback);
    }
}
