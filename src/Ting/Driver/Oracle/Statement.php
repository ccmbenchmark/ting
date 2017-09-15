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

namespace CCMBenchmark\Ting\Driver\Oracle;

use CCMBenchmark\Ting\Driver\QueryException;
use CCMBenchmark\Ting\Driver\StatementInterface;
use CCMBenchmark\Ting\Repository\CollectionInterface;
use CCMBenchmark\Ting\Logger\DriverLoggerInterface;

class Statement implements StatementInterface
{
    /**
     * @var mixed
     */
    private $statement;

    /**
     * @var array
     */
    private $paramsOrder;

    /**
     * @var string
     */
    private $connectionName;

    /**
     * @var string
     */
    private $database;

    /**
     * @var DriverLoggerInterface
     */
    private $logger;

    /**
     * @var resource
     */
    private $connection;

    /**
     * @var string|null
     */
    private $query;

    /**
     * @var bool
     */
    private $transactionOpened = false;

    /**
     * Statement constructor.
     *
     * @param mixed  $statement
     * @param array  $paramsOrder
     * @param string $connectionName
     * @param string $database
     */
    public function __construct($statement, array $paramsOrder, $connectionName, $database)
    {
        $this->statement      = $statement;
        $this->paramsOrder    = $paramsOrder;
        $this->connectionName = $connectionName;
        $this->database       = $database;
    }

    /**
     * @param resource $connection
     *
     * @return $this
     */
    public function setConnection($connection)
    {
        $this->connection = $connection;
        return $this;
    }

    /**
     * @param string $query
     *
     * @return $this
     */
    public function setQuery($query)
    {
        $this->query = (string) $query;
        return $this;
    }

    /**
     * @param boolean $transactionOpened
     *
     * @return $this
     */
    public function setTransactionOpened($transactionOpened)
    {
        $this->transactionOpened = (boolean) $transactionOpened;
        return $this;
    }

    /**
     * @param array $params
     * @param CollectionInterface $collection
     * @return mixed
     * @throws QueryException
     */
    public function execute(array $params, CollectionInterface $collection = null)
    {
        $values = array();
        foreach (array_keys($this->paramsOrder) as $key) {
            $values[] = $params[$key];
        }

        if ($this->logger !== null) {
            $this->logger->startStatementExecute($this->statement, $params);
        }

        foreach ($values as $key => $value) {
            oci_bind_by_name($this->statement, ':' . $key, $value);
        }

        $result = oci_execute(
            $this->statement,
            $this->transactionOpened === true ? OCI_NO_AUTO_COMMIT : OCI_COMMIT_ON_SUCCESS
        );
        if ($this->logger !== null) {
            $this->logger->stopStatementExecute($this->statement);
        }

        if ($result === false) {
            throw new QueryException(oci_error($this->connection));
        }

        if (oci_statement_type($this->statement) !== 'SELECT') {
            return true;
        }

        if ($collection !== null) {
            return $this->setCollectionWithResult(oci_fetch_array($this->statement), $collection);
        }

        return true;
    }

    /**
     * @param DriverLoggerInterface $logger
     * @return void
     */
    public function setLogger(DriverLoggerInterface $logger = null)
    {
        $this->logger = $logger;
    }

    /**
     * @param $resultResource
     * @param CollectionInterface $collection
     * @return bool
     * @throws QueryException
     *
     * @internal
     */
    public function setCollectionWithResult($resultResource, CollectionInterface $collection = null)
    {
        $result = new Result();
        $result->setConnectionName($this->connectionName);
        $result->setDatabase($this->database);
        $result->setResult($resultResource);
        $result->setQuery($this->query);

        $collection->set($result);
        return true;
    }
}
