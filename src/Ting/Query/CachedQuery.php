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
use CCMBenchmark\Ting\ConnectionPoolInterface;
use CCMBenchmark\Ting\Repository\CollectionInterface;
use CCMBenchmark\Ting\Repository\Metadata;

class CachedQuery extends Query
{
    protected $sql       = null;
    protected $params    = array();
    protected $ttl       = 0;
    /**
     * @var CacheInterface
     */
    protected $cache = null;
    /**
     * @var Query
     */
    protected $query = null;

    /**
     * @var int version number used in keys.
     */
    protected $version = 0;

    public function setCacheDriver(CacheInterface $cacheDriver)
    {
        $this->cache = $cacheDriver;

        return $this;
    }

    public function setTtl($ttl)
    {
        $this->ttl = (int) $ttl;

        return $this;
    }

    public function setVersion($version)
    {
        $this->version = $version;

        return $this;
    }

    /**
     * @param Metadata $metadata
     * @param ConnectionPoolInterface $connectionPool
     * @param CollectionInterface $collection
     * @param null $connectionType
     * @return CollectionInterface|mixed
     */
    public function execute(
        Metadata $metadata,
        ConnectionPoolInterface $connectionPool,
        CollectionInterface $collection = null,
        $connectionType = null
    ) {
        ksort($this->params);

        $key = $this->sql . serialize($this->params);
        $metadata->forConnectionNameAndDatabaseName(
            function ($connectionName, $databaseName) use (&$key) {
                $key .= $connectionName.'|'.$databaseName;
            }
        );
        $key = sha1($key) . '-' . $this->version;

        if ($values = $this->cache->get($key)) {
            $collection->setFromCache(true);
            $collection->fromArray($values);
        } else {
            $collection->setFromCache(false);

            parent::execute($metadata, $connectionPool, $collection, $connectionType);
            $this->cache->store($key, $collection->toArray(), $this->ttl);
        }

        return $collection;
    }
}
