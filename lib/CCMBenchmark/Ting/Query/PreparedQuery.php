<?php
/***********************************************************************
 *
 * Ting - PHP Datamapper
 * ==========================================
 *
 * Copyright (C) 2014 CCM Benchmark Group. (http://www.ccmbenchmark.com)
 *
 ***********************************************************************
 *
 * Licensed under the Apache License, Version 2.0 (the "License"); you
 * may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 *
 *     http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or
 * implied. See the License for the specific language governing
 * permissions and limitations under the License.
 *
 **********************************************************************/

namespace CCMBenchmark\Ting\Query;

use CCMBenchmark\Ting\Driver\StatementInterface;
use CCMBenchmark\Ting\Repository\Collection;

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
     * @param \CCMBenchmark\Ting\Repository\Collection $collection
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
