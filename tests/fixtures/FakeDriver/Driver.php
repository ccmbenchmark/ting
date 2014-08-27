<?php

namespace tests\fixtures\FakeDriver;

use fastorm\Driver\DriverInterface;
use fastorm\Driver\StatementInterface;
use fastorm\Entity\Collection;
use fastorm\Query\QueryAbstract;

class Driver implements DriverInterface
{

    public static function forConnectionKey($connectionName, $database, callable $callback)
    {
        $callback($connectionName);
    }

    public function connect($hostname, $username, $password, $port)
    {

    }

    public function execute($sql, $params = array(), $queryType = QueryAbstract::TYPE_RESULT, Collection $collection = null)
    {

    }

    public function prepare(
        $sql,
        callable $callback,
        $queryType = QueryAbstract::TYPE_RESULT,
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
