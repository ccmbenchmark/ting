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

namespace CCMBenchmark\Ting\Query\Cached;

use CCMBenchmark\Ting\Cache\CacheInterface;
use CCMBenchmark\Ting\Query\QueryException;
use CCMBenchmark\Ting\Repository\CollectionInterface;

class Query extends \CCMBenchmark\Ting\Query\Query
{

    /**
     * @var CacheInterface|null
     */
    protected $cache = null;

    /**
     * @var int|null
     */
    protected $ttl = null;

    /**
     * @var int
     */
    protected $version = 1;

    /**
     * @var bool
     */
    protected $force = false;

    /**
     * @param CacheInterface $cache
     * @return void
     */
    public function setCache(CacheInterface $cache)
    {
        $this->cache = $cache;
    }

    /**
     * @param int $ttl
     * @return $this
     */
    public function setTtl($ttl)
    {
        $this->ttl = (int) $ttl;
        return $this;
    }

    /**
     * @param int $version
     * @return $this
     */
    public function setVersion($version)
    {
        $this->version = $version;
        return $this;
    }

    /**
     * @param bool $value
     * @return $this
     */
    public function setForce($value)
    {
        $this->force = (bool) $value;
        return $this;
    }

    /**
     * @param CollectionInterface $collection
     * @return CollectionInterface
     */
    public function query(CollectionInterface $collection = null)
    {
        $this->checkTtl();

        if ($collection === null) {
            $collection = $this->collectionFactory->get();
        }

        $key      = $this->getCacheKey($this->params);
        $isCached = $this->checkCache($key, $collection);
        if ($isCached === true) {
            return $collection;
        }

        parent::query($collection);
        $this->cache->store($key, $collection->toArray(), $this->ttl);

        return $collection;
    }

    /**
     * @param array $params
     * @return string
     */
    protected function getCacheKey(array $params)
    {
        ksort($params);
        $key = $this->sql . serialize($params);
        $this->connection->forCacheKey(
            function ($cacheKey) use (&$key) {
                $key = $key . $cacheKey;
            }
        );
        $key = sha1($key) . '-' . $this->version;

        return $key;
    }

    /**
     * @param string $key
     * @param CollectionInterface $collection
     * @return bool
     * @throws QueryException
     */
    protected function checkCache($key, CollectionInterface $collection)
    {
        $collection->setFromCache(false);

        if ($this->force === true) {
            return false;
        }

        $this->checkTtl();
        $data = $this->cache->get($key);

        if ($data !== null) {
            $collection->setFromCache(true);
            $collection->fromArray($data);
            return true;
        }

        return false;
    }

    /**
     * @throws QueryException
     */
    protected function checkTtl()
    {
        if ($this->ttl === null) {
            throw new QueryException("You should call setTtl to use query method");
        }
    }
}
