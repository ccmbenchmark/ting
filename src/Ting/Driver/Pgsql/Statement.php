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

use CCMBenchmark\Ting\Driver\Exception;
use CCMBenchmark\Ting\Driver\QueryException;
use CCMBenchmark\Ting\Driver\StatementInterface;
use CCMBenchmark\Ting\Query\QueryAbstract;
use CCMBenchmark\Ting\Repository\CollectionInterface;

class Statement implements StatementInterface
{

    protected $connection    = null;
    protected $statementName = null;
    protected $queryType     = null;
    protected $query         = null;


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
            throw new Exception('setQueryType should use one of constant Statement::TYPE_*');
        }

        $this->queryType = $type;

        return $this;
    }


    public function execute(array $params, CollectionInterface $collection = null)
    {
        $values = array();
        foreach (array_keys($this->paramsOrder) as $key) {
            if ($params[$key] instanceof \DateTime) {
                $params[$key] = $params[$key]->format('Y-m-d H:i:s');
            }
            $values[] = &$params[$key];
        }

        $result = pg_execute($this->connection, $this->statementName, $values);
        return $this->setCollectionWithResult($result, $collection);
    }

    /**
     * @param $resultResource
     * @param CollectionInterface $collection
     * @return bool
     * @throws QueryException
     */
    public function setCollectionWithResult($resultResource, CollectionInterface $collection = null)
    {
        if ($collection === null) { // update or insert
            if ($this->queryType === QueryAbstract::TYPE_INSERT) {
                $resultResource = pg_query($this->connection, 'SELECT lastval()');
                $row = pg_fetch_row($resultResource);
                return $row[0];
            }

            return pg_affected_rows($resultResource);
        }

        if ($resultResource === false) {
            throw new QueryException(pg_result_error($this->connection));
        }

        $result = new Result($resultResource);
        $result->setQuery($this->query);

        $collection->set($result);
        return true;
    }

    /**
     * @throws \CCMBenchmark\Ting\Driver\Exception
     */
    public function close()
    {
        if ($this->statementName === null) {
            throw new Exception('statement->close can\'t be called before statement->execute');
        }

        pg_query($this->connection, 'DEALLOCATE "' . $this->statementName . '"');
    }
}
