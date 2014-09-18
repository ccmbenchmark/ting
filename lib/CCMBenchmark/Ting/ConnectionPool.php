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

class ConnectionPool implements ConnectionPoolInterface
{

    protected $connectionConfig = array();
    protected $connections = array();

    /**
     * @throws \CCMBenchmark\Ting\Exception
     */
    public function setConfig($config)
    {
        $this->connectionConfig = $config;
    }

    /**
     * @throws \CCMBenchmark\Ting\Exception
     */
    public function connect($connectionName, $database, callable $callback)
    {
        if (isset($this->connectionConfig[$connectionName]) === false) {
            throw new Exception('Connection not found: ' . $connectionName);
        }

        $driverClass = $this->connectionConfig[$connectionName]['namespace'] . '\\Driver';

        $driverClass::forConnectionKey(
            $connectionName,
            $database,
            function ($connectionKey) use ($driverClass, $connectionName, $callback, $database) {
                if (isset($this->connections[$connectionKey]) === false) {
                    $driver = new $driverClass();
                    $driver->connect(
                        $this->connectionConfig[$connectionName]['host'],
                        $this->connectionConfig[$connectionName]['user'],
                        $this->connectionConfig[$connectionName]['password'],
                        $this->connectionConfig[$connectionName]['port']
                    );
                    $this->connections[$connectionKey] = $driver;
                }

                $this->connections[$connectionKey]->setDatabase($database);

                $callback($this->connections[$connectionKey]);
            }
        );

        return $this;
    }
}
