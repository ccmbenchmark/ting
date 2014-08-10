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
        $params = array();

        foreach ($this->params as $key => $value) {
            switch (gettype($value)) {
                case "integer":
                    $type = "i";
                    break;
                case "double":
                    $type = "f";
                    break;
                default:
                    $type = "s";
            }

            $params[$key] = array(
                'type'  => $type,
                'value' => $value
            );
        }

        if ($collection === null) {
            $collection = new Collection();
        }


        $driver->prepare(
            $this->sql,
            function ($statement, $paramsOrder, $driverStatement) use ($params, $collection) {
                $statement->execute($driverStatement, $params, $paramsOrder, $collection);
            }
        );

        return $this;
    }
}
