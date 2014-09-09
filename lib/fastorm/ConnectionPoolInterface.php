<?php


namespace fastorm;


interface ConnectionPoolInterface
{
    public function setConfig($config);
    public function connect($connectionName, $database, callable $callback);
}
