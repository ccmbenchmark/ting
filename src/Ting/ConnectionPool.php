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

namespace CCMBenchmark\Ting;

use CCMBenchmark\Ting\Repository\CollectionInterface;

class ConnectionPool implements ConnectionPoolInterface
{

    /**
     * @var array
     */
    protected $connectionConfig = array();

    /**
     * @var array
     */
    protected $connections = array();

    /**
     * @param array $config
     */
    public function setConfig($config)
    {
        $this->connectionConfig = $config;
    }

    /**
     * @param string $name
     * @return array
     */
    protected function preSetMaster($name)
    {
        $config = $this->connectionConfig[$name]['master'];
        $driverClass = $this->connectionConfig[$name]['namespace'] . '\\Driver';

        return [$config, $driverClass];
    }

    /**
     * @param string $name
     * @return array
     */
    protected function preSetSlave($name)
    {
        $driverClass = $this->connectionConfig[$name]['namespace'] . '\\Driver';

        if (isset($this->connectionConfig[$name]['slaves']) === false
            || $this->connectionConfig[$name]['slaves'] === []
        ) {
            $config = $this->connectionConfig[$name]['master'];
            return [$config, $driverClass];
        }

        $randomKey = array_rand($this->connectionConfig[$name]['slaves']);
        $config = $this->connectionConfig[$name]['slaves'][$randomKey];

        return [$config, $driverClass];
    }

    /**
     * @param string $name
     * @param string $database
     * @param string $sql
     * @param array $params
     * @param CollectionInterface $collection
     * @return mixed
     */
    public function onMasterDoExecute($name, $database, $sql, $params, CollectionInterface $collection = null)
    {
        list ($config, $driverClass) = $this->preSetMaster($name);
        return $this->connect($config, $driverClass, $database)->execute($sql, $params, $collection);
    }

    /**
     * @param string $name
     * @param string $database
     * @param string $sql
     * @param array $params
     * @param CollectionInterface $collection
     * @return mixed
     */
    public function onSlaveDoExecute($name, $database, $sql, $params, CollectionInterface $collection = null)
    {
        list ($config, $driverClass) = $this->preSetSlave($name);
        return $this->connect($config, $driverClass, $database)->execute($sql, $params, $collection);
    }

    /**
     * @param array $config
     * @param string $driverClass
     * @param string $database
     * @return mixed
     */
    protected function connect($config, $driverClass, $database)
    {

        $connectionKey = $driverClass::getConnectionKey($config, $database);

        if (isset($this->connections[$connectionKey]) === false) {
            $driver = new $driverClass();
            $driver->connect(
                $config['host'],
                $config['user'],
                $config['password'],
                $config['port']
            );
            $this->connections[$connectionKey] = $driver;
        }

        $this->connections[$connectionKey]->setDatabase($database);
        return $this->connections[$connectionKey];
    }

    /**
     * @param string $name
     * @param string $database
     */
    public function onMasterStartTransaction($name, $database)
    {
        list ($config, $driverClass) = $this->preSetMaster($name);
        $this->connect($config, $driverClass, $database)->startTransaction();
    }

    /**
     * @param string $name
     * @param string $database
     */
    public function onMasterRollback($name, $database)
    {
        list ($config, $driverClass) = $this->preSetMaster($name);
        $this->connect($config, $driverClass, $database)->rollback();
    }

    /**
     * @param string $name
     * @param string $database
     */
    public function onMasterCommit($name, $database)
    {
        list ($config, $driverClass) = $this->preSetMaster($name);
        $this->connect($config, $driverClass, $database)->commit();
    }

    /**
     * @param string $name
     * @param string $database
     * @return int
     */
    public function onMasterDoGetInsertId($name, $database)
    {
        list ($config, $driverClass) = $this->preSetMaster($name);
        return $this->connect($config, $driverClass, $database)->getInsertId();
    }

    /**
     * @param string $name
     * @param string $database
     * @return int
     */
    public function onSlaveDoGetInsertId($name, $database)
    {
        list ($config, $driverClass) = $this->preSetSlave($name);
        return $this->connect($config, $driverClass, $database)->onSlaveDoGetInsertId();
    }

    /**
     * @param string $name
     * @param string $database
     * @return int
     */
    public function onMasterDoGetAffectedRows($name, $database)
    {
        list ($config, $driverClass) = $this->preSetMaster($name);
        return $this->connect($config, $driverClass, $database)->getAffectedRows();
    }

    /**
     * @param string $name
     * @param string $database
     * @return mixed
     */
    public function onSlaveDoGetAffectedRows($name, $database)
    {
        list ($config, $driverClass) = $this->preSetSlave($name);
        return $this->connect($config, $driverClass, $database)->onSlaveDoGetAffectedRows();
    }
}
