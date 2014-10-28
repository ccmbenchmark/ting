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
use CCMBenchmark\Ting\Query\QueryAbstract;
use CCMBenchmark\Ting\Repository\CollectionInterface;

class Statement implements StatementInterface
{

    /**
     * @var \mysqli_stmt|Object|null
     */
    protected $driverStatement = null;

    /**
     * @var array|null
     */
    protected $paramsOrder = null;

    /**
     * @param \mysqli_stmt|Object $driverStatement
     * @param array $paramsOrder
     */
    public function __construct($driverStatement, array $paramsOrder)
    {
        $this->driverStatement = $driverStatement;
        $this->paramsOrder     = $paramsOrder;
    }

    /**
     * @param array $params
     * @param CollectionInterface $collection
     * @return bool|CollectionInterface
     * @throws QueryException
     */
    public function execute(array $params, CollectionInterface $collection = null)
    {
        $types = '';
        $values = array();

        foreach (array_keys($this->paramsOrder) as $key) {
            switch (gettype($params[$key])) {
                case "object":
                    if ($params[$key] instanceof \DateTime) {
                        $params[$key] = $params[$key]->format('Y-m-d H:i:s');
                        $type = "s";
                    }
                    break;
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
        call_user_func_array(array($this->driverStatement, 'bind_param'), $values);

        $this->driverStatement->execute();

        if ($collection !== null) {
            return $this->setCollectionWithResult($collection);
        }

        if ($this->driverStatement->affected_rows === -1) {
            return false;
        }

        return true;
    }

    /**
     * @param CollectionInterface $collection
     * @return CollectionInterface
     * @throws QueryException
     */
    public function setCollectionWithResult(CollectionInterface $collection)
    {
        $result = $this->driverStatement->get_result();

        if ($result === false) {
            throw new QueryException($this->driverStatement->error, $this->driverStatement->errno);
        }

        $collection->set(new Result($result));
        return $collection;
    }

    /**
     * @throws Exception
     */
    public function close()
    {
        if ($this->driverStatement === null) {
            throw new Exception('statement->close can\'t be called before statement->execute');
        }

        $this->driverStatement->close();
    }
}
