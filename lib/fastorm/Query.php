<?php

namespace fastorm;

use fastorm\Driver\DriverInterface;
use fastorm\Driver\StatementInterface;
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
        $driver->prepare(
            $this->sql,
            function (
                StatementInterface $statement,
                $paramsOrder,
                $driverStatement
            ) use (
                &$result,
                $collection
            ) {
                $result = $statement->execute($driverStatement, $this->params, $paramsOrder, $collection);
            }
        );

        return $result;
    }
}
