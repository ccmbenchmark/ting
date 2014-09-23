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
    protected $connectionsTypesToConfig = array();

    public function setConfig($config)
    {
        $this->connectionConfig = $config;
    }

    /**
     * @param $connectionName
     * @param $database
     * @param $connectionType
     * @param \Closure $callback
     * @return $this
     * @throws Exception
     */
    public function connect($connectionName, $database, $connectionType, \Closure $callback)
    {
        if (isset($this->connectionConfig[$connectionName]) === false) {
            throw new Exception('Connection not found: ' . $connectionName);
        }

        $driverClass = $this->connectionConfig[$connectionName]['namespace'] . '\\Driver';

        $connectionConfig = $this->retrieveApplicableConnectionConf($connectionName, $database, $connectionType);

        $driverClass::forConnectionKey(
            $connectionConfig,
            $database,
            function ($connectionKey) use (
                $driverClass,
                $connectionConfig,
                $connectionName,
                $callback,
                $database,
                $connectionType
            ) {
                if (isset($this->connections[$connectionKey]) === false) {
                    $driver = new $driverClass();
                    $driver->connect(
                        $connectionConfig['host'],
                        $connectionConfig['user'],
                        $connectionConfig['password'],
                        $connectionConfig['port']
                    );
                    $this->connections[$connectionKey] = $driver;
                }

                $this->connections[$connectionKey]->setDatabase($database);

                $callback($this->connections[$connectionKey]);
            }
        );

        return $this;
    }

    public function retrieveApplicableConnectionConf($connectionName, $connectionType)
    {
        if (
            $connectionType == self::CONNECTION_SLAVE
            && isset($this->connectionConfig[$connectionName]['slaves'])
            && count($this->connectionConfig[$connectionName]['slaves']) > 0
        ) {
            if (
                isset($this->connectionsTypesToConfig[$connectionName])
                &&
                isset($this->connectionsTypesToConfig[$connectionName][$connectionType])
            ) {
                /**
                 * It's a slave connection and we already have choose a slave : we use the same one
                 * In this way we avoid opening one connection per slave because of round-robin
                 */
                $connectionConfig = $this->connectionsTypesToConfig[$connectionName][$connectionType];
            } else {
                $randomKey = array_rand($this->connectionConfig[$connectionName]['slaves']);
                $connectionConfig = $this->connectionConfig[$connectionName]['slaves'][$randomKey];
            }
        } else {
            /**
             * In this case : we only have a master or the connexionType has been set to master
             */
            $connectionConfig = $this->connectionConfig[$connectionName]['master'];
        }

        $this->connectionsTypesToConfig[$connectionName][$connectionType] = $connectionConfig;
        return $connectionConfig;
    }
}
