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

    protected $connection    = null;
    protected $statementName = null;
    protected $queryType     = null;
    protected $query         = null;

    /**
     * @var DriverLoggerInterface|null
     */
    protected $logger        = null;

    /**
     * @param       $statementName
     * @param array $paramsOrder
     */
    public function __construct($statementName, array $paramsOrder)
    {
        $this->statementName = $statementName;
        $this->paramsOrder   = $paramsOrder;
    }
    /**
     * @param $connection
     * @return $this
     */
    public function setConnection($connection)
    {
        $this->connection = $connection;

        return $this;
    }

    /**
     * @param $query
     * @return $this
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
            $values[] = &$params[$key];
        }

        if ($this->logger !== null) {
            $this->logger->startStatementExecute($this->statementName, $params);
        }
        $result = pg_execute($this->connection, $this->statementName, $values);
        if ($this->logger !== null) {
            $this->logger->stopStatementExecute($this->statementName);
        }

        if ($result === false) {
            throw new QueryException(pg_result_error($this->connection));
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
     */
    public function setCollectionWithResult($resultResource, CollectionInterface $collection = null)
    {
        $result = new Result($resultResource);
        $result->setQuery($this->query);

        $collection->set($result);
        return true;
    }

    /**
     * Deallocate the current prepared statement
     */
    public function close()
    {
        pg_query($this->connection, 'DEALLOCATE "' . $this->statementName . '"');
    }
}
