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

use CCMBenchmark\Ting\Driver\DriverInterface;
use CCMBenchmark\Ting\Logger\DriverLoggerInterface;

class ConnectionPool implements ConnectionPoolInterface
{

    /**
     * @var array
     */
    protected $connectionConfig = array();

    /**
     * @var array
     */
    protected $databaseOptions = array();

    /**
     * @var array
     */
    protected $connectionSlaves = array();

    /**
     * @var array
     */
    protected $connections = array();

    /**
     * @var DriverLoggerInterface|null
     */
    protected $logger = null;

    /**
     * @param DriverLoggerInterface $logger
     */
    public function __construct(DriverLoggerInterface $logger = null)
    {
        $this->logger = $logger;
    }

    /**
     * @param array $config
     */
    public function setConfig($config)
    {
        $this->connectionConfig = $config;
    }

    public function setDatabaseOptions($options)
    {
        $this->databaseOptions = $options;
    }

    /**
     * Return the master connection
     *
     * @param $name
     * @param $database
     * @return DriverInterface
     * @throws Exception
     */
    public function master($name, $database)
    {
        if (isset($this->connectionConfig[$name]['master']) === false) {
            throw new Exception('Connection not found: ' . $name);
        }
        $config = $this->connectionConfig[$name]['master'];
        $driverClass = $this->connectionConfig[$name]['namespace'] . '\\Driver';

        $charset = null;

        if (isset($this->connectionConfig[$name]['charset']) === true) {
            $charset = $this->connectionConfig[$name]['charset'];
        }

        return $this->connect($config, $driverClass, $database, $name, $charset);
    }

    /**
     * Return always the same slave connection
     *
     * @param string $name
     * @param string $database
     * @return DriverInterface
     * @throws Exception
     */
    public function slave($name, $database)
    {
        if (isset($this->connectionConfig[$name]) === false) {
            throw new Exception('Connection not found: ' . $name);
        }
        $driverClass = $this->connectionConfig[$name]['namespace'] . '\\Driver';

        if (isset($this->connectionConfig[$name]['slaves']) === false
            || $this->connectionConfig[$name]['slaves'] === []
        ) {
            return $this->master($name, $database);
        }

        if (isset($this->connectionSlaves[$name]) === false) {
            /**
             * It's a slave connection and we do not have choosen a slave. We randomly take one & store datas.
             * In this way we avoid opening one connection per slave because of round-robin.
             */

            $randomKey = array_rand($this->connectionConfig[$name]['slaves']);
            $this->connectionSlaves[$name] = $this->connectionConfig[$name]['slaves'][$randomKey];
        }

        $connectionConfig = $this->connectionSlaves[$name];

        $charset = null;

        if (isset($this->connectionConfig[$name]['charset']) === true) {
            $charset = $this->connectionConfig[$name]['charset'];
        }

        return $this->connect($connectionConfig, $driverClass, $database, $name, $charset);
    }

    /**
     * @param array $config
     * @param string $driverClass
     * @param string $database
     * @param string $name connection name
     * @param string $charset
     * @return DriverInterface
     * @throws Exception
     */
    protected function connect($config, $driverClass, $database, $name, $charset = null)
    {

        if (isset($config['user']) === false) {
            $config['user'] = null;
        }

        if (isset($config['password']) === false) {
            $config['password'] = null;
        }

        $connectionKey = $driverClass::getConnectionKey($config, $database);

        if (isset($this->connections[$connectionKey]) === false) {
            $driver = new $driverClass();

            if ($this->logger !== null) {
                $this->logger->addConnection($name, spl_object_hash($driver), $config);
                $driver->setLogger($this->logger);
            }

            $driver->connect(
                $config['host'],
                $config['user'],
                $config['password'],
                $config['port']
            );
            $this->connections[$connectionKey] = $driver;
        }

        $this->connections[$connectionKey]->setName($name);
        $this->connections[$connectionKey]->setDatabase($database);

        if ($charset !== null) {
            $this->connections[$connectionKey]->setCharset($charset);
        }

        if (method_exists($this->connections[$connectionKey], 'setTimezone')) {
            $timezone = isset($this->databaseOptions[$database]['timezone']) !== false ? $this->databaseOptions[$database]['timezone'] : null;
            $this->connections[$connectionKey]->setTimezone($timezone);
        }

        return $this->connections[$connectionKey];
    }

    /**
     * Close all opened connections
     */
    public function closeAll()
    {
        foreach ($this->connections as $connectionKey => $connection) {
            $connection->close();
            unset($this->connections[$connectionKey]);
        }
    }

    /**
     * @param string $name connection name
     * @return string
     * @throws Exception
     */
    public function getDriverClass($name)
    {
        if (isset($this->connectionConfig[$name]) === false) {
            throw new Exception('Connection not found: ' . $name);
        }

        return $this->connectionConfig[$name]['namespace'] . '\\Driver';
    }
}
