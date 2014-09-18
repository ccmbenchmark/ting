<?php

namespace CCMBenchmark\Ting\Query;

use CCMBenchmark\Ting\Driver\StatementInterface;
use CCMBenchmark\Ting\Entity\Collection;

class PreparedQuery extends QueryAbstract
{
    protected $paramsOrder = array();
    /**
     * @var StatementInterface
     */
    protected $statement = null;
    protected $driverStatement = null;
    protected $prepared = false;

    /**
     * @return $this
     * @throws QueryException
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
            },
            $this->queryType,
            $this->statement
        );
        $this->prepared = true;

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

        if ($this->prepared === false) {
            $this->prepare();
        }

        return $this->statement->execute($this->driverStatement, $this->params, $this->paramsOrder, $collection);
    }
}
