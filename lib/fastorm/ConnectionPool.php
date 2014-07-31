<?php

namespace fastorm;

class ConnectionPool
{

    private static $instance = null;

    private function __construct($config)
    {

    }

    public static function getInstance($config = array())
    {
        if (self::$instance === null) {
            if (count($config) === 0) {
                throw new Exception('First call to ConnectionPool must pass configuration in parameters');
            }

        	self::$instance = new self($config);
        }

        return self::$instance;
    }
}
