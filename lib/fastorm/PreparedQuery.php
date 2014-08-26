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

    /**
     * @param $sql Query
     * @param array $params
     */
    public function __construct($sql, $params = array())
    {
        $this->sql = $sql;
        $this->params = $params;
    }

    /**
     * @param DriverInterface $driver
     * @return $this
     */
    public function setDriver(DriverInterface $driver)
    {
        $this->driver = $driver;

        return $this;
    }

    /**
     * @param array $params
     * @return $this
     * @throws \InvalidArgumentException
     */
    public function setParams($params)
    {
        if (!is_array($params)) {
            throw new \InvalidArgumentException('Params should be an array');
        }
        $this->params = $params;

        return $this;
    }

    /**
     * @return $this
     * @throws Driver\QueryException
     */
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

    /**
     * @param Collection $collection
     * @return mixed
     * @throws Driver\QueryException
     */
    public function execute(
        Collection $collection = null
    ) {
        if ($this->driver === null) {
            throw new QueryException('You have to set the driver before to call execute');
        }

        if ($this->prepared === false) {
            $this->prepare();
        }

        return $this->statement->execute($this->driverStatement, $this->params, $this->paramsOrder, $collection);
    }
}
