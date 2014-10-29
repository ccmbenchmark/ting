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

class Memcached implements CacheInterface
{
    protected $connected    = false;
    protected $connection   = null;
    protected $config       = [];

    public function setConfig(array $config)
    {
        $this->config = $config;
    }

    public function setConnection($connection)
    {
        $this->connection = $connection;
    }

    /**
     * @internal Getter because no other way to do a dependency injection for \Memcached
     */
    public function getPersistentId()
    {
        if (isset($this->config['persistentId']) === false) {
            return null;
        }

        return $this->config['persistentId'];
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
            throw new Exception('Must setConnection priory to use Memcached');
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
        $value = $this->connection->get($key);

        if ($value === false) {
            return null;
        }

        return $value;
    }

    public function getMulti(array $keys)
    {
        $this->connect();
        $values = $this->connection->getMulti($keys);

        if ($values === false) {
            return null;
        }

        return $values;
    }

    public function store($key, $value, $ttl)
    {
        $this->connect();
        return $this->connection->set($key, $value, $ttl);
    }

    public function storeMulti($values, $ttl)
    {
        $this->connect();
        return $this->connection->setMulti($values, $ttl);
    }

    public function delete($key)
    {
        $this->connect();
        return $this->connection->delete($key);
    }

    public function deleteMulti(array $keys)
    {
        $this->connect();
        return $this->connection->deleteMulti($keys);
    }

    public function replace($key, $value, $ttl)
    {
        $this->connect();
        return $this->connection->replace($key, $value, $ttl);
    }
}
