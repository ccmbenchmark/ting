<?php

namespace fastorm;

use fastorm\Entity\Collection;

class SimpleQuery extends Query
{

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
