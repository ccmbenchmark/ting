<?php


namespace fastorm;

use fastorm\Driver\DriverInterface;
use fastorm\Entity\Collection;

abstract class Query
{
    const TYPE_RESULT   = 1;
    const TYPE_AFFECTED = 2;
    const TYPE_INSERT   = 3;

    protected $sql = '';
    protected $params = array();
    protected $driver = null;

    final public function __construct($sql, $params = array())
    {
        $this->sql = $sql;
        $this->params = $params;
        $this->setQueryType();
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
     * @throws Driver\QueryException
     */
    abstract public function execute(
        Collection $collection = null
    );

    final private function setQueryType()
    {

    }
}
