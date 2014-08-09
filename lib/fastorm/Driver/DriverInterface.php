<?php

namespace fastorm\Driver;

use fastorm\Entity\Hydrator;

interface DriverInterface
{

    public function connect($hostname, $username, $password, $port);
    public function prepare($sql, callable $callback, StatementInterface $statement = null);
    public function setDatabase($database);
    public function ifIsError(callable $callback);
    public function ifIsNotConnected(callable $callback);
}
