<?php

namespace tests\fixtures\FakeDriver;

use fastorm\Driver\DriverInterface;
use fastorm\Driver\StatementInterface;

class Driver implements DriverInterface
{
    public function connect($hostname, $username, $password, $port)
    {

    }

    public function setDatabase($database)
    {
        $this->database = $database;
    }

    public function execute($sql, $columnsMeta = array(), StatementInterface $statement = null)
    {

    }

    public function ifIsError(callable $callback)
    {

    }

    public function ifIsNotConnected(callable $callback)
    {

    }
}
