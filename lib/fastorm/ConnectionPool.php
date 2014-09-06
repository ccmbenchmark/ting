<?php

namespace fastorm;

class ConnectionPool implements ConnectionPoolInterface
{

    protected $connectionConfig = array();
    protected $connections = array();

    /**
     * @throws \fastorm\Exception
     */
    public function setConfig($config)
    {
        $this->connectionConfig = $config;
    }

    /**
     * @throws \fastorm\Exception
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
