<?php

namespace tests\fixtures\FakeDriver;

use fastorm\Driver\DriverInterface;
use fastorm\Driver\StatementInterface;

class Driver implements DriverInterface
{
    public function connect($hostname, $username, $password, $port)
    {

    }

    public function prepare(
        $sql,
        callable $callback,
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
