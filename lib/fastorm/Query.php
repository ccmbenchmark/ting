<?php

namespace fastorm;

use fastorm\Driver\DriverInterface;
use fastorm\Driver\StatementInterface;
use fastorm\Entity\Collection;

class Query
{

    protected $sql = '';
    protected $params = array();
    protected $driver = null;

    public function __construct($sql, $params = array())
    {
        $this->sql = $sql;
        $this->params = $params;
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
     * @param DriverInterface $driver
     * @return $this
     */
    public function setDriver(DriverInterface $driver)
    {
        $this->driver = $driver;

        return $this;
    }

    /**
     * @param Collection $collection
     * @return mixed
     * @throws QueryException
     */
    public function execute(
        Collection $collection = null
    ) {
        if ($this->driver === null) {
            throw new QueryException('You have to set the driver before to call execute');
        }

        return $this->driver->executeSimpleQuery($this->sql, $this->params, $collection);

        /*$this->driver->prepare(
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

        return $result;*/
    }
}
