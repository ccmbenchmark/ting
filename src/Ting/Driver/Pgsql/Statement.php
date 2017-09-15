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

namespace CCMBenchmark\Ting\Driver\Pgsql;

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

    protected $connection    = null;
    protected $statementName = null;
    protected $paramsOrder   = [];
    protected $queryType     = null;
    protected $query         = null;

    /**
     * @var DriverLoggerInterface|null
     */
    protected $logger = null;

    /**
     * @param mixed  $statementName
     * @param array  $paramsOrder
     * @param string $connectionName
     * @param string $database
     */
    public function __construct($statementName, array $paramsOrder, $connectionName, $database)
    {
        $this->statementName  = $statementName;
        $this->paramsOrder    = $paramsOrder;
        $this->connectionName = $connectionName;
        $this->database       = $database;
    }

    /**
     * @param $connection
     * @return $this
     *
     * @internal
     */
    public function setConnection($connection)
    {
        $this->connection = $connection;

        return $this;
    }

    /**
     * @param $query
     * @return $this
     *
     * @internal
     */
    public function setQuery($query)
    {
        $this->query = (string) $query;

        return $this;
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
     * Execute the actual statement with the given parameters
     * @param array               $params
     * @param CollectionInterface $collection
     * @return bool|mixed
     * @throws QueryException
     */
    public function execute(array $params, CollectionInterface $collection = null)
    {
        $values = array();
        foreach (array_keys($this->paramsOrder) as $key) {
            $values[] = $params[$key];
        }

        if ($this->logger !== null) {
            $this->logger->startStatementExecute($this->statementName, $params);
        }
        $result = pg_execute($this->connection, $this->statementName, $values);
        if ($this->logger !== null) {
            $this->logger->stopStatementExecute($this->statementName);
        }

        if ($result === false) {
            throw new QueryException(pg_errormessage($this->connection));
        }

        if ($collection !== null) {
            return $this->setCollectionWithResult($result, $collection);
        }

        return true;
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

    /**
     * Deallocate the current prepared statement
     */
    protected function close()
    {
        pg_query($this->connection, 'DEALLOCATE "' . $this->statementName . '"');
    }

    /**
     * @internal
     */
    public function __destruct()
    {
        $this->close();
    }
}
