<?php

namespace fastorm;

use fastorm\Driver\DriverInterface;
use fastorm\Entity\Collection;

class Query
{

    protected $sql = '';
    protected $params = array();

    public function __construct($sql, $params = array())
    {
        $this->sql = $sql;
        $this->params = $params;
    }

    public function execute(
        DriverInterface $driver,
        Collection $collection = null
    ) {
        if ($collection === null) {
            $collection = new Collection();
        }

        $driver->prepare(
            $this->sql,
            function ($statement, $paramsOrder, $driverStatement) use ($params, $collection) {
                $statement->execute($driverStatement, $this->params, $paramsOrder, $collection);
            }
        );

        return $this;
    }
}
