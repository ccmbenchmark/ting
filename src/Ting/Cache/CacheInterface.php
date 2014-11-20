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

namespace CCMBenchmark\Ting\Cache;

use CCMBenchmark\Ting\Logger\CacheLoggerInterface;

interface CacheInterface
{
    /**
     * Inject configuration to the cache driver
     * @param array $config
     * @return mixed
     */
    public function setConfig(array $config);

    /**
     * Add the ability to log operations
     *
     * @param CacheLoggerInterface $logger
     * @return void
     */
    public function setLogger(CacheLoggerInterface $logger = null);

    /**
     * Retrieve one key
     *
     * @param $key
     * @return mixed
     */
    public function get($key);

    /**
     * Retrieve multiple keys
     *
     * @param array $keys
     * @return mixed
     */
    public function getMulti(array $keys);

    /**
     * Store 1 key / value. TTL of 0 means infinite
     *
     * @param $key
     * @param $value
     * @param $ttl
     * @return mixed
     */
    public function store($key, $value, $ttl);

    /**
     * Store multiple key values (associative array). TTL of 0 means infinite
     *
     * @param array $values
     * @param $ttl
     * @return mixed
     */
    public function storeMulti(array $values, $ttl);

    /**
     * Delete one key from the storage
     *
     * @param $key
     * @return mixed
     */
    public function delete($key);

    /**
     * Delete multiple keys from the storage
     *
     * @param array $keys
     * @return mixed
     */
    public function deleteMulti(array $keys);

    /**
     * Replace the value of the provided key with the provided value. TTL of 0 means infinite.
     *
     * @param $key
     * @param $value
     * @param $ttl
     * @return mixed
     */
    public function replace($key, $value, $ttl);
}
