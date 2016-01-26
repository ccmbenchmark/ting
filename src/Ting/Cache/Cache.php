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

use Doctrine\Common\Cache\Cache as DoctrineCache;
use CCMBenchmark\Ting\Logger\CacheLoggerInterface;

class Cache implements CacheInterface
{
    /**
     * @var CacheLoggerInterface|null
     */
    private $logger = null;

    /**
     * @var DoctrineCache
     */
    private $cache;

    /**
     * @param DoctrineCache $cache
     */
    public function setCache(DoctrineCache $cache)
    {
        $this->cache = $cache;
    }

    /**
     * Add the ability to log operations
     *
     * @param CacheLoggerInterface $logger
     */
    public function setLogger(CacheLoggerInterface $logger)
    {
        $this->logger = $logger;
    }

    /**
     * Logs an operation with $this->logger if provided
     *
     * @param $type
     * @param $operation
     * @return void
     */
    protected function log($type, $operation)
    {
        if ($this->logger !== null) {
            $this->logger->startOperation($type, $operation);
        }
    }

    /**
     * Flag the last operation logged as stopped
     *
     * @param $miss boolean optional : required if last operation was a read
     * @return void
     */
    protected function stopLog($miss = false)
    {
        if ($this->logger !== null) {
            $this->logger->stopOperation($miss);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function delete($id)
    {
        $this->log(CacheLoggerInterface::OPERATION_DELETE, $id);
        $result = $this->cache->delete($id);
        $this->stopLog();

        return $result;
    }

    /**
     * {@inheritdoc}
     */
    public function fetch($id)
    {
        $this->log(CacheLoggerInterface::OPERATION_GET, $id);
        $value = $this->cache->fetch($id);
        $this->stopLog(($value === false));

        return $value;
    }

    /**
     * {@inheritdoc}
     */
    public function contains($id)
    {
        $this->log(CacheLoggerInterface::OPERATION_EXIST, $id);
        $value = $this->cache->contains($id);
        $this->stopLog(($value === false));

        return $value;
    }

    /**
     * {@inheritdoc}
     */
    public function save($id, $data, $lifeTime = 0)
    {
        $this->log(CacheLoggerInterface::OPERATION_STORE, $id);
        $result = $this->cache->save($id, $data, $lifeTime);
        $this->stopLog();

        return $result;
    }

    /**
     * {@inheritdoc}
     */
    public function getStats()
    {
        return $this->cache->getStats();
    }
}
