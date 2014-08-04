<?php

namespace fastorm\Driver;

interface DriverInterface
{

    public function connect($hostname, $username, $password, $port);
    public function setDatabase($database);
    public function prepare($sql, callable $callback, StatementInterface $statement);
    public function ifIsError(callable $callback);
    public function ifIsNotConnected(callable $callback);
}
