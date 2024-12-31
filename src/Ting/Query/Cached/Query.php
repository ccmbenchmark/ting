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

use CCMBenchmark\Ting\Exception;
use Doctrine\Common\Cache\Cache;
use CCMBenchmark\Ting\Query\QueryException;
use CCMBenchmark\Ting\Repository\CollectionInterface;

class Query extends \CCMBenchmark\Ting\Query\Query
{
    /**
     * @var Cache|null
     */
    protected $cache = null;

    /**
     * @var int|null
     */
    protected $ttl = null;

    /**
     * @var string|null
     */
    protected $cacheKey = null;

    /**
     * @var int
     */
    protected $version = 1;

    /**
     * @var bool
     */
    protected $force = false;

    /**
     * Set the cache interface to the actual query
     * @param Cache $cache
     * @return void
     */
    public function setCache(Cache $cache)
    {
        $this->cache = $cache;
    }

    /**
     * Define the ttl for the current query
     * @param int $ttl
     * @return $this
     */
    public function setTtl($ttl)
    {
        $this->ttl = (int) $ttl;
        return $this;
    }

    /**
     * Define the cache key for the current query
     *
     * @param $cacheKey
     * @return $this
     */
    public function setCacheKey($cacheKey)
    {
        $this->cacheKey = $cacheKey;

        return $this;
    }

    /**
     * Set the version used in the key
     * @param int $version
     * @return $this
     */
    public function setVersion($version)
    {
        $this->version = $version;
        return $this;
    }

    /**
     * Set force mode. If enabled : always do query
     * @param bool $value
     * @return $this
     */
    public function setForce($value)
    {
        $this->force = (bool) $value;
        return $this;
    }

    /**
     * Check if query is in cache or execute the query and store the result
     * @param CollectionInterface $collection
     * @return CollectionInterface
     * @throws Exception
     * @throws QueryException
     */
    public function query(CollectionInterface $collection = null)
    {
        $this->checkTtl();

        if ($collection === null) {
            $collection = $this->collectionFactory->get();
        }

        $isCached = $this->checkCache($this->cacheKey, $collection);
        if ($isCached === true) {
            return $collection;
        }

        parent::query($collection);
        $this->cache->save($this->cacheKey, $collection->toCache(), $this->ttl);

        return $collection;
    }

    /**
     * Check if a key is available in cache and fill collection if it's available
     * @param string $key
     * @param CollectionInterface $collection
     * @return bool
     * @throws QueryException
     */
    protected function checkCache($key, CollectionInterface $collection)
    {
        if ($key === null) {
            throw new QueryException('You must call setCacheKey to use query method');
        }

        $collection->setFromCache(false);

        if ($this->force === true) {
            return false;
        }

        $this->checkTtl();
        $result = $this->cache->fetch($key);

        if ($result !== false) {
            $collection->fromCache($result);
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
