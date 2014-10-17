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
    protected $keyPrefix    = '';

    public function __construct($connection = null)
    {
        if ($connection !== null) {
            $this->connection = $connection;
            $this->connected = true;
        }
    }

    public function setConfig(array $config)
    {
        $this->config = $config;
        if (isset($this->config['keyPrefix'])) {
            $this->keyPrefix = $this->config['keyPrefix'];
        }
    }

    private function connect()
    {
        if ($this->connected === true) {
            return true;
        }
        if (count($this->config) === 0) {
            throw new Exception('Must setConfig priory to connect to memcached');
        }
        $this->connection = new \Memcached($this->config['persistentId']);

        if (count($this->config['options']) > 0) {
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
        return $this->connection->get($this->keyPrefix . $key);
    }

    public function getMulti(array $keys)
    {
        $this->connect();

        $keys = array_map(function ($key) {
                return $this->keyPrefix . $key;
        }, $keys);

        return $this->connection->getMulti($keys);
    }

    public function store($key, $value, $ttl)
    {
        $this->connect();
        return $this->connection->set($this->keyPrefix . $key, $value, $ttl);
    }

    public function storeMulti($values, $ttl)
    {
        $this->connect();

        array_walk(
            $values,
            function ($value, &$key) {
                $key = $this->keyPrefix . $key;
            }
        );

        return $this->connection->setMulti($values, $ttl);
    }

    public function delete($key)
    {
        $this->connect();
        return $this->connection->delete($this->keyPrefix . $key);
    }

    public function deleteMulti(array $keys)
    {
        $this->connect();
        $keys = array_map(function ($key) {
                return $this->keyPrefix . $key;
        }, $keys);
        return $this->connection->deleteMulti($keys);
    }

    public function replace($key, $value, $ttl)
    {
        $this->connect();
        return $this->connection->replace($this->keyPrefix . $key, $value, $ttl);
    }
}
