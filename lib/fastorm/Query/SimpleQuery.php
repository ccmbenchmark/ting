<?php

namespace fastorm\Query;

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

        if ($collection === null && $this->queryType == Query::TYPE_RESULT) {
            $collection = new Collection();
        }

        return $this->driver->execute($this->sql, $this->params, $this->queryType, $collection);
    }
}
