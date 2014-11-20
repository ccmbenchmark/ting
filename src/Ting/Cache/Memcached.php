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

use CCMBenchmark\Ting\Exception;
use CCMBenchmark\Ting\Logger\CacheLoggerInterface;

class Memcached implements CacheInterface
{
    protected $connected    = false;
    protected $connection   = null;
    protected $config       = [];
    /**
     * @var CacheLoggerInterface|null
     */
    protected $logger       = null;

    public function setConfig(array $config)
    {
        $this->config = $config;
    }

    public function setConnection($connection)
    {
        $this->connection = $connection;
    }

    /**
     * Add the ability to log operations
     *
     * @param CacheLoggerInterface $logger
     * @return void
     */
    public function setLogger(CacheLoggerInterface $logger = null)
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
     * @internal Getter because no other way to do a dependency injection for \Memcached
     */
    public function getPersistentId()
    {
        if (isset($this->config['persistent_id']) === false) {
            return null;
        }

        return $this->config['persistent_id'];
    }

    private function connect()
    {
        if ($this->connected === true) {
            return true;
        }

        if ($this->config === []) {
            throw new Exception('Must setConfig priory to use Memcached');
        }

        if (isset($this->config['servers']) === false) {
            throw new Exception('Config must have servers to use Memcached');
        }

        if ($this->connection === null) {
            $this->connection = new \Memcached($this->getPersistentId());
        }

        if (isset($this->config['options']) === true && is_array($this->config['options']) === true) {
            $this->connection->setOptions($this->config['options']);
        }

        if (count($this->connection->getServerList()) !== count($this->config['servers'])) {
            $this->connection->resetServerList();
            $this->connection->addServers($this->config['servers']);
        }

        $this->connected = true;
        return true;
    }

    public function get($key)
    {
        $this->connect();

        $this->log(CacheLoggerInterface::OPERATION_GET, $key);
        $value = $this->connection->get($key);
        $this->stopLog(($value === false));

        if ($value === false) {
            return null;
        }

        return $value;
    }

    public function getMulti(array $keys)
    {
        $this->connect();

        $this->log(CacheLoggerInterface::OPERATION_GET_MULTI, $keys);
        $values = $this->connection->getMulti($keys);
        $this->stopLog(($values === false));

        if ($values === false) {
            return null;
        }

        return $values;
    }

    public function store($key, $value, $ttl)
    {
        $this->connect();

        $this->log(CacheLoggerInterface::OPERATION_STORE, $key);
        $result = $this->connection->set($key, $value, $ttl);
        $this->stopLog();

        return $result;
    }

    public function storeMulti(array $values, $ttl)
    {
        $this->connect();

        $this->log(CacheLoggerInterface::OPERATION_STORE_MULTI, array_keys($values));
        $result = $this->connection->setMulti($values, $ttl);
        $this->stopLog();

        return $result;
    }

    public function delete($key)
    {
        $this->connect();

        $this->log(CacheLoggerInterface::OPERATION_DELETE, $key);
        $result = $this->connection->delete($key);
        $this->stopLog();

        return $result;
    }

    public function deleteMulti(array $keys)
    {
        $this->connect();

        $this->log(CacheLoggerInterface::OPERATION_DELETE_MULTI, $keys);
        $result = $this->connection->deleteMulti($keys);
        $this->stopLog();

        return $result;
    }

    public function replace($key, $value, $ttl)
    {
        $this->connect();

        $this->log(CacheLoggerInterface::OPERATION_REPLACE, $key);
        $result = $this->connection->replace($key, $value, $ttl);
        $this->stopLog();

        return $result;
    }
}
