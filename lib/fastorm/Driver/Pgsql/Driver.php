<?php

namespace fastorm\Driver\Pgsql;

use fastorm\Driver\DriverInterface;
use fastorm\Driver\StatementInterface;
use fastorm\Driver\Exception;
use fastorm\Driver\QueryException;
use fastorm\Entity\Collection;

class Driver implements DriverInterface
{

    /**
     * @var ressource pgsql
     */
    protected $connection = null;

    public static function forConnectionKey($connectionName, $database, callable $callback)
    {
        $callback($connectionName . '|' . $database);
    }

    public function __construct()
    {

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

    public function prepare(
        $sql,
        callable $callback,
        Collection $collection = null,
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

        $queryType = Statement::TYPE_RESULT;

        if (strpos($sql, 'UPDATE') === 0 || strpos($sql, 'DELETE') === 0) {
            $queryType = Statement::TYPE_AFFECTED;
        } elseif (strpos($sql, 'INSERT') === 0) {
            $queryType = Statement::TYPE_INSERT;
        }

        $statement->setConnection($this->connection);
        $statement->setQueryType($queryType);
        $statement->setQuery($sql);

        $callback($statement, $paramsOrder, $statementName, $collection);
    }

    public function ifIsError(callable $callback)
    {
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
}
