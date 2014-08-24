<?php

namespace fastorm;

class ConnectionPool
{

    protected static $instance = null;
    protected $connectionConfig = array();
    protected $connections = array();

    /**
     * @throws \fastorm\Exception
     */
    protected function __construct($config)
    {
        if (isset($config['connections']) === false) {
            throw new Exception('Configuration must have "connections" key');
        }

        $this->connectionConfig = $config['connections'];
    }

    /**
     * @throws \fastorm\Exception
     */
    public static function getInstance($config = array())
    {
        if (self::$instance === null) {
            if (count($config) === 0) {
                throw new Exception('First call to ConnectionPool must pass configuration in parameters');
            }

            self::$instance = new self($config);
        }

        return self::$instance;
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
