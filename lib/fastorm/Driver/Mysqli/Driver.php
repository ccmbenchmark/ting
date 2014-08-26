<?php

namespace fastorm\Driver\Mysqli;

use fastorm\Driver\DriverInterface;
use fastorm\Driver\StatementInterface;
use fastorm\Driver\Exception;
use fastorm\Driver\QueryException;
use fastorm\Query\Query;

class Driver implements DriverInterface
{

    /**
     * @var object driver
     */
    protected $driver = null;

    /**
     * @var object driver connection
     */
    protected $connection = null;

    /**
     * @var string
     */
    protected $currentDatabase = null;

    /**
     * @var bool
     */
    protected $connected = false;

    /**
     * @var bool
     */
    protected $transactionOpened = false;

    public function __construct($connection = null, $driver = null)
    {
        if ($connection === null) {
            $this->connection = \mysqli_init();
        } else {
            $this->connection = $connection;
        }

        if ($this->driver === null) {
            $this->driver = new \mysqli_driver();
        } else {
            $this->driver = $driver;
        }
    }

    public static function forConnectionKey($connectionName, $database, callable $callback)
    {
        $callback($connectionName);
    }

    /**
     * @throws \fastorm\Driver\Exception
     */
    public function connect($hostname, $username, $password, $port = 3306)
    {

        $this->driver->report_mode = MYSQLI_REPORT_STRICT;

        try {
            $this->connected = $this->connection->real_connect($hostname, $username, $password, null, $port);
        } catch (\Exception $e) {
            throw new Exception('Connect Error: ' . $e->getMessage(), $e->getCode());
        }

        return $this;
    }

    /**
     * @throws \fastorm\Driver\Exception
     */
    public function setDatabase($database)
    {

        if ($this->currentDatabase === $database) {
            return $this;
        }

        $this->connection->select_db($database);

        $this->ifIsError(function () {
            throw new Exception('Select database error: ' . $this->connection->error, $this->connection->errno);
        });

        $this->currentDatabase = $database;

        return $this;
    }

    public function ifIsError(callable $callback)
    {
        if ($this->connection->error !== '') {
            $callback($this->connection->error);
        }

        return $this;
    }

    public function execute($sql, callable $callback, $queryType)
    {

    }

    /**
     * @throws \fastorm\Driver\QueryException
     */
    public function prepare(
        $sql,
        callable $callback,
        $queryType = Query::TYPE_RESULT,
        StatementInterface $statement = null
    ) {
        $sql = preg_replace_callback(
            '/(?<!\\\):([a-zA-Z0-9_-]+)/',
            function ($match) use (&$paramsOrder) {
                $paramsOrder[$match[1]] = null;
                return '?';
            },
            $sql
        );

        $sql = str_replace('\:', ':', $sql);

        if ($statement === null) {
            $statement = new Statement();
        }

        $driverStatement = $this->connection->prepare($sql);

        if ($driverStatement === false) {
            $this->ifIsError(function () use ($sql) {
                throw new QueryException($this->connection->error . ' (Query: ' . $sql . ')', $this->connection->errno);
            });
        }

        $queryType = Query::TYPE_RESULT;
        $sqlCompare = trim(strtoupper($sql));
        /* @todo We REALLY need to do this better :  we don't like playing riddle */
        if (strpos($sqlCompare, 'UPDATE') === 0 || strpos($sqlCompare, 'DELETE') === 0) {
            $queryType = Query::TYPE_AFFECTED;
        } elseif (strpos($sqlCompare, 'INSERT') === 0) {
            $queryType = Query::TYPE_INSERT;
        }

        $statement->setQueryType($queryType);

        $callback($statement, $paramsOrder, $driverStatement);

        return $this;
    }

    public function ifIsNotConnected(callable $callback)
    {
        if ($this->connected === false) {
            $callback();
        }

        return $this;
    }

    public function escapeFields($fields, callable $callback)
    {
        foreach ($fields as &$field) {
            $field = '`' . $field . '`';
        }

        $callback($fields);
        return $this;
    }

    /**
     * @throws \fastorm\Driver\Exception
     */
    public function startTransaction()
    {
        if ($this->transactionOpened === true) {
            throw new Exception('Cannot start another transaction');
        }
        $this->connection->begin_transaction();
        $this->transactionOpened = true;
    }

    /**
     * @throws \fastorm\Driver\Exception
     */
    public function commit()
    {
        if ($this->transactionOpened === false) {
            throw new Exception('Cannot commit no transaction');
        }
        $this->connection->commit();
        $this->transactionOpened = false;
    }

    /**
     * @throws \fastorm\Driver\Exception
     */
    public function rollback()
    {
        if ($this->transactionOpened === false) {
            throw new Exception('Cannot rollback no transaction');
        }
        $this->connection->rollback();
        $this->transactionOpened = false;
    }

    public function isTransactionOpened()
    {
        return $this->transactionOpened;
    }
}
