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

namespace CCMBenchmark\Ting\Driver\Mysqli;

use CCMBenchmark\Ting\Driver\Exception;
use CCMBenchmark\Ting\Driver\QueryException;
use CCMBenchmark\Ting\Driver\StatementInterface;
use CCMBenchmark\Ting\Entity\Collection;
use CCMBenchmark\Ting\Query\QueryAbstract;

class Statement implements StatementInterface
{

    protected $driverStatement = null;
    protected $queryType       = null;

    /**
     * @param $type
     * @return $this
     * @throws \CCMBenchmark\Ting\Driver\Exception
     */
    public function setQueryType($type)
    {
        if (
            in_array(
                $type,
                array(QueryAbstract::TYPE_RESULT, QueryAbstract::TYPE_AFFECTED, QueryAbstract::TYPE_INSERT)
            ) === false
        ) {
            throw new Exception('setQueryType should use one of constant QueryAbstract::TYPE_*');
        }

        $this->queryType = $type;

        return $this;
    }

    /**
     * @param $driverStatement
     * @param $params
     * @param $paramsOrder
     * @param Collection $collection
     * @return bool
     */
    public function execute($driverStatement, $params, $paramsOrder, Collection $collection = null)
    {
        $this->driverStatement = $driverStatement;
        $types = '';
        $values = array();
        foreach (array_keys($paramsOrder) as $key) {
            switch (gettype($params[$key])) {
                case "integer":
                    $type = "i";
                    break;
                case "double":
                    $type = "d";
                    break;
                default:
                    $type = "s";
            }
            $types .= $type;
            $values[] = &$params[$key];

        }

        array_unshift($values, $types);
        call_user_func_array(array($driverStatement, 'bind_param'), $values);

        $driverStatement->execute();

        return $this->setCollectionWithResult($driverStatement, $collection);
    }

    /**
     * @param $driverStatement
     * @param Collection $collection
     * @return bool|Result
     * @throws \CCMBenchmark\Ting\Driver\QueryException
     */
    public function setCollectionWithResult($driverStatement, Collection $collection = null)
    {
        if ($this->queryType !== QueryAbstract::TYPE_RESULT) {
            if ($this->queryType === QueryAbstract::TYPE_INSERT) {
                    return $driverStatement->insert_id;
            }

            $result = $driverStatement->affected_rows;

            if ($result === null || $result === -1) {
                return false;
            }
            return $result;
        }

        $result = $driverStatement->get_result();

        if ($result === false) {
            throw new QueryException($driverStatement->error, $driverStatement->errno);
        }

        $collection->set(new Result($result));
        return true;
    }

    /**
     * @throws \CCMBenchmark\Ting\Driver\Exception
     */
    public function close()
    {
        if ($this->driverStatement === null) {
            throw new Exception('statement->close can\'t be called before statement->execute');
        }

        $this->driverStatement->close();
    }
}
