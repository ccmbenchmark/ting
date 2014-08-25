<?php

namespace fastorm;

use fastorm\Driver\DriverInterface;
use fastorm\Driver\QueryException;
use fastorm\Driver\StatementInterface;
use fastorm\Entity\Collection;

class PreparedQuery
{

    protected $sql = '';
    protected $params = array();

    /**
     * @var DriverInterface
     */
    protected $driver = null;
    protected $paramsOrder = array();
    protected $statement = null;
    protected $driverStatement = null;
    protected $prepared = false;

    public function __construct($sql, $params = array())
    {
        $this->sql = $sql;
        $this->params = $params;
    }

    public function setDriver(DriverInterface $driver)
    {
        $this->driver = $driver;

        return $this;
    }

    public function setParams($params = array())
    {
        $this->params = $params;

        return $this;
    }

    public function prepare()
    {
        if ($this->driver === null) {
            throw new QueryException('You have to set the driver before to call prepare');
        }

        if ($this->prepared === true) {
            return $this;
        }

        $this->driver->prepare(
            $this->sql,
            function (
                StatementInterface $statement,
                $paramsOrder,
                $driverStatement
            ) {
                $this->statement = $statement;
                $this->paramsOrder = $paramsOrder;
                $this->driverStatement = $driverStatement;
            }
        );
        $this->prepared = true;

        return $this;
    }

    public function execute(
        Collection $collection = null
    ) {
        if ($this->prepared === false) {
            throw new QueryException('Please prepare your query');
        }

        return $this->statement->execute($this->driverStatement, $this->params, $this->paramsOrder, $collection);
    }
}
