<?php

namespace fastorm\Driver\Mysqli;

use fastorm\Driver\DriverInterface;
use fastorm\Driver\Exception;
use fastorm\Driver\QueryException;

class Driver implements DriverInterface
{

    /**
     * @var \mysqli $connection
     */
    protected $connection;

    /**
     * @var object driver
     */
    protected $driver = null;

    /**
     * @var object driver connection
     */
    protected $driverConnection = null;

    /**
     * @var bool
     */
    protected $connected = false;


    public function __construct($driverConnection = null, $driver = null)
    {
        if ($driverConnection === null) {
            $this->driverConnection = \mysqli_init();
        } else {
            $this->driverConnection = $driverConnection;
        }

        if ($this->driver === null) {
            $this->driver = new \mysqli_driver();
        } else {
            $this->driver = $driver;
        }
    }


    public function connect($hostname, $username, $password, $port = 3306)
    {

        $this->driver->report_mode = MYSQLI_REPORT_STRICT;

        try {
            // @ to hide getaddrinfo failed, error is still catched by try/catch
            $this->connection = @$this->driverConnection->real_connect($hostname, $username, $password, null, $port);
            $this->connected = true;
        } catch (\Exception $e) {
            throw new Exception('Connect Error : ' . $e->getMessage(), $e->getCode());
        }

        return $this;
    }

    public function setDatabase($database)
    {

        $this->connection->select_db($database);

        $this->ifIsError(function () {
            throw new Exception('Select database error: ' . $this->connection->error, $this->connection->errno);
        });

        return $this;
    }

    public function ifIsError(callable $callback)
    {
        if ($this->connection->error !== '') {
            $callback($this->connection->error);
        }

        return $this;
    }

    public function prepare($sql, callable $callback, \fastorm\Driver\StatementInterface $statement = null)
    {

        $paramsOrder = array();
        $sql = preg_replace_callback(
            '/:([a-zA-Z0-9_-]+)/',
            function ($match) use (&$paramsOrder) {
                $paramsOrder[$match[1]] = null;
                return '?';
            },
            $sql
        );

        $mysqliStatement = $this->connection->prepare($sql);

        if ($mysqliStatement === false) {
            $this->ifIsError(function () use($sql) {
                throw new QueryException($this->connection->error . ' (Query: ' . $sql . ')', $this->connection->errno);
            });
        }

        if ($statement === null) {
            $statement = new Statement();
        }

        $statement->setStatement($mysqliStatement);
        $statement->setParamsOrder($paramsOrder);

        $callback($statement);

        return $this;
    }

    /**
     * @return boolean
     */
    public function ifIsNotConnected(callable $callback)
    {
        if ($this->connected === false) {
            $callback();
        }

        return $this;
    }
}
