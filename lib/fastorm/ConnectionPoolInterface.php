<?php


namespace fastorm;


interface ConnectionPoolInterface
{
    public static function getInstance($config = array());
    public function connect($connectionName, $database, callable $callback);
}
