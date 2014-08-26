<?php

namespace fastorm\Driver\Pgsql;

use fastorm\Driver\DriverInterface;
use fastorm\Driver\StatementInterface;
use fastorm\Driver\Exception;
use fastorm\Driver\QueryException;
use fastorm\Query\Query;

class Driver implements DriverInterface
{

    /**
     * @var resource pgsql
     */
    protected $connection = null;

    /**
     * @var bool
     */
    protected $transactionOpened = false;

    public static function forConnectionKey($connectionName, $database, callable $callback)
    {
        $callback($connectionName . '|' . $database);
    }

    public function connect($hostname, $username, $password, $port)
    {
        $this->dsn = 'host=' . $hostname . ' user=' . $username . ' password=' . $password . ' port=' . $port;
        return $this;
    }

    public function setDatabase($database)
    {
        if ($this->connection !== null) {
            return $this;
        }

        $resource = pg_connect($this->dsn . ' dbname=' . $database);

        if ($resource === false) {
            throw new Exception('Connect Error: ' . $this->dsn . ' dbname=' . $database);
        }
        $this->connection = $resource;

        return $this;
    }

    public function execute($sql, callable $callback, $queryType = Query::TYPE_RESULT)
    {

    }

    public function prepare(
        $sql,
        callable $callback,
        $queryType = Query::TYPE_RESULT,
        StatementInterface $statement = null
    ) {
        $paramsOrder = array();
        $sql = preg_replace_callback(
            '/(?<!\\\):([a-zA-Z0-9_-]+)/',
            function ($match) use (&$paramsOrder) {
                $paramsOrder[$match[1]] = null;
                return '$' . count($paramsOrder);
            },
            $sql
        );

        $sql = str_replace('\:', ':', $sql);

        if ($statement === null) {
            $statement = new Statement();
        }

        $statementName = md5($sql);
        $result = pg_prepare($this->connection, $statementName, $sql);

        if ($result === false) {
            $this->ifIsError(function () use ($sql) {
                throw new QueryException(pg_last_error($this->connection) . ' (Query: ' . $sql . ')');
            });
        }

        $statement
            ->setConnection($this->connection)
            ->setQueryType($queryType);
        $statement->setQuery($sql);

        $callback($statement, $paramsOrder, $statementName);
        return $this;
    }

    public function ifIsError(callable $callback)
    {
        $error = '';
        if ($this->connection !== null) {
            $error = pg_last_error($this->connection);
        }

        if ($error !== '') {
            $callback();
        }
    }

    public function ifIsNotConnected(callable $callback)
    {
        if ($this->connection === null) {
            $callback();
        }
    }

    public function escapeFields($fields, callable $callback)
    {
        foreach ($fields as &$field) {
            $field = '"' . $field . '"';
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
        pg_query($this->connection, 'BEGIN');
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
        pg_query($this->connection, 'COMMIT');
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
        pg_query($this->connection, 'ROLLBACK');
        $this->transactionOpened = false;
    }
}
