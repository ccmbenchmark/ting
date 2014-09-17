<?php

namespace fastorm\Driver;

use fastorm\Entity\Collection;
use fastorm\Query\QueryAbstract;

interface DriverInterface
{

    public function connect($hostname, $username, $password, $port);
    public function execute(
        $sql,
        $params = array(),
        $queryType = QueryAbstract::TYPE_RESULT,
        Collection $collection = null
    );
    public function prepare(
        $sql,
        callable $callback,
        $queryType = QueryAbstract::TYPE_RESULT,
        StatementInterface $statement = null
    );
    public function setDatabase($database);
    public function ifIsError(callable $callback);
    public function ifIsNotConnected(callable $callback);
    public function escapeFields($fields, callable $callback);
    public function startTransaction();
    public function rollback();
    public function commit();
    public static function forConnectionKey($connectionName, $database, callable $callback);
}