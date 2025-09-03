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
use CCMBenchmark\Ting\Driver\Pgsql\Driver;
use CCMBenchmark\Ting\Exceptions\ConnectionException;
use CCMBenchmark\Ting\Logger\DriverLoggerInterface;

class ConnectionPool implements ConnectionPoolInterface
{
    /**
     * @var array
     */
    protected $connectionConfig = [];

    /**
     * @var array
     */
    protected $databaseOptions = [];

    /**
     * @var array
     */
    protected $connectionSlaves = [];

    /**
     * @var array<string, DriverInterface>
     */
    protected $connections = [];

    public function __construct(protected ?DriverLoggerInterface $logger = null)
    {
    }

    /**
     * @param array $config
     */
    public function setConfig($config): void
    {
        $this->connectionConfig = $config;
    }

    public function setDatabaseOptions(array $options): void
    {
        $this->databaseOptions = $options;
    }

    /**
     * Return the master connection
     *
     * @throws ConnectionException
     */
    public function master(string $name, string $database): DriverInterface
    {
        if (isset($this->connectionConfig[$name]['master']) === false) {
            throw new ConnectionException('Connection not found: ' . $name);
        }
        $config = $this->connectionConfig[$name]['master'];
        /** @var class-string<DriverInterface> $driverClass */
        $driverClass = $this->connectionConfig[$name]['namespace'] . '\\Driver';

        $charset = null;

        if (isset($this->connectionConfig[$name]['charset'])) {
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
     * @throws ConnectionException
     */
    public function slave($name, $database): DriverInterface
    {
        if (isset($this->connectionConfig[$name]) === false) {
            throw new ConnectionException('Connection not found: ' . $name);
        }
        /** @var class-string<DriverInterface> $driverClass */
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

        if (isset($this->connectionConfig[$name]['charset'])) {
            $charset = $this->connectionConfig[$name]['charset'];
        }

        return $this->connect($connectionConfig, $driverClass, $database, $name, $charset);
    }

    /**
     * @param array $config
     * @param class-string<DriverInterface> $driverClass
     * @param string $database
     * @param string $name connection name
     * @param string $charset
     * @return DriverInterface
     * @throws Exception
     */
    protected function connect($config, $driverClass, $database, $name, $charset = null): DriverInterface
    {

        if (isset($config['user']) === false) {
            $config['user'] = null;
        }

        if (isset($config['password']) === false) {
            $config['password'] = null;
        }

        $connectionKey = $driverClass::getConnectionKey($config, $database);

        if (isset($this->connections[$connectionKey]) === false) {
            /** @var DriverInterface $driver */
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

        /*
         * Methods setDatabase, setCharset and setTimezone have no impact unless the parameter's value is different
         * than the staled value.
         */
        $this->connections[$connectionKey]->setName($name);
        $this->connections[$connectionKey]->setDatabase($database);

        if ($charset !== null) {
            $this->connections[$connectionKey]->setCharset($charset);
        }

        if (method_exists($this->connections[$connectionKey], 'setTimezone')) {
            $timezone = $this->databaseOptions[$database]['timezone'] ?? null;
            $this->connections[$connectionKey]->setTimezone($timezone);
        }

        return $this->connections[$connectionKey];
    }

    /**
     * Close all opened connections
     */
    public function closeAll(): void
    {
        foreach ($this->connections as $connectionKey => $connection) {
            $connection->close();
            unset($this->connections[$connectionKey]);
        }
    }

    /**
     * @param string $name connection name
     * @return string
     * @throws ConnectionException
     */
    public function getDriverClass(string $name): string
    {
        if (isset($this->connectionConfig[$name]) === false) {
            throw new ConnectionException('Connection not found: ' . $name);
        }

        return $this->connectionConfig[$name]['namespace'] . '\\Driver';
    }
}
