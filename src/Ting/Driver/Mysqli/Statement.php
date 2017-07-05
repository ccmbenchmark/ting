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
use CCMBenchmark\Ting\Logger\DriverLoggerInterface;
use CCMBenchmark\Ting\Repository\CollectionInterface;

class Statement implements StatementInterface
{

    /**
     * @var string
     */
    protected $connectionName;

    /**
     * @var string database name
     */
    protected $database  = '';

    /**
     * @var mixed
     */
    protected $driverStatement = null;

    /**
     * @var array|null
     */
    protected $paramsOrder = null;

    /**
     * @var DriverLoggerInterface|null
     */
    protected $logger = null;

    /**
     * @var string spl_object_hash of current object
     */
    protected $objectHash = '';

    /**
     * @param \mysqli_stmt|Object $driverStatement
     * @param array               $paramsOrder
     * @param string              $connectionName
     * @param string              $database
     */
    public function __construct($driverStatement, array $paramsOrder, $connectionName, $database)
    {
        $this->driverStatement = $driverStatement;
        $this->paramsOrder     = $paramsOrder;
        $this->connectionName  = $connectionName;
        $this->database        = $database;
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

        if ($this->logger !== null) {
            $this->logger->startStatementExecute($this->objectHash, $params);
        }
        $this->driverStatement->execute();
        if ($this->logger !== null) {
            $this->logger->stopStatementExecute($this->objectHash);
        }

        if ($this->driverStatement->errno > 0) {
            throw new QueryException($this->driverStatement->error, $this->driverStatement->errno);
        }

        $result = $this->driverStatement->get_result();

        if ($collection !== null) {
            return $this->setCollectionWithResult($result, $collection);
        }

        return true;
    }

    /**
     * @param DriverLoggerInterface $logger
     * @return void
     */
    public function setLogger(DriverLoggerInterface $logger = null)
    {
        if ($logger !== null) {
            $this->logger = $logger;
            $this->objectHash = spl_object_hash($this->driverStatement);
        }
    }

    /**
     * @param \mysqli_result $resultData
     * @param CollectionInterface $collection
     * @return bool
     *
     * @internal
     */
    public function setCollectionWithResult($resultData, CollectionInterface $collection)
    {
        $result = new Result();
        $result->setConnectionName($this->connectionName);
        $result->setDatabase($this->database);
        $result->setResult($resultData);
        $collection->set($result);
        return true;
    }

    /**
     * @throws Exception
     *
     * @internal
     */
    protected function close()
    {
        $this->driverStatement->close();
    }

    /**
     * @internal
     */
    public function __destruct()
    {
        $this->close();
    }
}
