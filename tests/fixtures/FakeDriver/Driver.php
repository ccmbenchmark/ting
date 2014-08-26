<?php

namespace tests\fixtures\FakeDriver;

use fastorm\Driver\DriverInterface;
use fastorm\Driver\StatementInterface;
use fastorm\Query\Query;

class Driver implements DriverInterface
{

    public static function forConnectionKey($connectionName, $database, callable $callback)
    {
        $callback($connectionName);
    }

    public function connect($hostname, $username, $password, $port)
    {

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

    }

    public function setDatabase($database)
    {
        $this->database = $database;
    }

    public function ifIsError(callable $callback)
    {

    }

    public function ifIsNotConnected(callable $callback)
    {

    }

    public function escapeFields($fields, callable $callback)
    {

    }

    public function startTransaction()
    {

    }

    public function rollback()
    {

    }

    public function commit()
    {

    }
}
