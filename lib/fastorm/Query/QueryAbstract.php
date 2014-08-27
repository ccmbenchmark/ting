<?php


namespace fastorm\Query;

use fastorm\Driver\DriverInterface;
use fastorm\Entity\Collection;

/** @todo move to QueryAbstract */
abstract class QueryAbstract
{
    const TYPE_RESULT   = 1;
    const TYPE_AFFECTED = 2;
    const TYPE_INSERT   = 3;

    protected $sql = '';
    protected $params = array();
    protected $queryType = self::TYPE_RESULT;

    /**
     * @var DriverInterface $driver
     */
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
     * @throws QueryException
     */
    abstract public function execute(
        Collection $collection = null
    );

    final private function setQueryType()
    {
        $queryType = self::TYPE_RESULT;
        $sqlCompare = trim(strtoupper($this->sql));
        /* @todo We REALLY need to do this better :  we don't like playing riddle */
        if (strpos($sqlCompare, 'UPDATE') === 0 || strpos($sqlCompare, 'DELETE') === 0) {
            $queryType = self::TYPE_AFFECTED;
        } elseif (strpos($sqlCompare, 'INSERT') === 0 || strpos($sqlCompare, 'REPLACE' === 0)) {
            $queryType = self::TYPE_INSERT;
        }
        $this->queryType = $queryType;
    }
}
