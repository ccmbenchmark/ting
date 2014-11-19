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
use CCMBenchmark\Ting\Logger\Driver\DriverLoggerInterface;

class ConnectionPool implements ConnectionPoolInterface
{

    /**
     * @var array
     */
    protected $connectionConfig = array();

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

    /**
     * Return the master connection
     *
     * @param $name
     * @param $database
     * @throws Exception
     * @return DriverInterface
     */
    public function master($name, $database)
    {
        if (!isset($this->connectionConfig[$name]['master'])) {
            throw new Exception('Connection not found: ' . $name);
        }
        $config = $this->connectionConfig[$name]['master'];
        $driverClass = $this->connectionConfig[$name]['namespace'] . '\\Driver';

        return $this->connect($config, $driverClass, $database);
    }

    /**
     * Return always the same slave connection
     *
     * @param $name
     * @param $database
     * @throws Exception
     * @return DriverInterface
     */
    public function slave($name, $database)
    {
        if (!isset($this->connectionConfig[$name])) {
            throw new Exception('Connection not found: ' . $name);
        }
        $driverClass = $this->connectionConfig[$name]['namespace'] . '\\Driver';

        if (isset($this->connectionConfig[$name]['slaves']) === false
            || $this->connectionConfig[$name]['slaves'] === []
        ) {
            return $this->master($name, $database);
        }

        if (
            !isset($this->connectionSlaves[$name])
        ) {
            /**
             * It's a slave connection and we do not have choosen a slave. We randomly take one & store datas.
             * In this way we avoid opening one connection per slave because of round-robin.
             */

            $randomKey = array_rand($this->connectionConfig[$name]['slaves']);
            $this->connectionSlaves[$name] = $this->connectionConfig[$name]['slaves'][$randomKey];
        }

        $connectionConfig = $this->connectionSlaves[$name];

        return $this->connect($connectionConfig, $driverClass, $database);
    }

    /**
     * @param array $config
     * @param string $driverClass
     * @param string $database
     * @return DriverInterface
     */
    protected function connect($config, $driverClass, $database)
    {

        $connectionKey = $driverClass::getConnectionKey($config, $database);

        if (isset($this->connections[$connectionKey]) === false) {
            $driver = new $driverClass();

            if ($this->logger !== null) {
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

        $this->connections[$connectionKey]->setDatabase($database);
        return $this->connections[$connectionKey];
    }
}
