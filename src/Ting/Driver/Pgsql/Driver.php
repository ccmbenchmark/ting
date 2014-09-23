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

use CCMBenchmark\Ting\Driver\DriverInterface;
use CCMBenchmark\Ting\Driver\Exception;
use CCMBenchmark\Ting\Driver\QueryException;
use CCMBenchmark\Ting\Driver\StatementInterface;
use CCMBenchmark\Ting\Query\QueryAbstract;
use CCMBenchmark\Ting\Repository\Collection;

class Driver implements DriverInterface
{

    /**
     * @var resource pgsql
     */
    protected $connection = null;

    /**
     * @var bool
     */
    protected $transactionOpened = false;

    public static function forConnectionKey($connectionConfig, $database, \Closure $callback)
    {
        $callback(
            $connectionConfig['host'] . '|' .
            $connectionConfig['port'] . '|' .
            $connectionConfig['user'] . '|' .
            $connectionConfig['password'] . '|' .
            $database
        );
    }

    public function connect($hostname, $username, $password, $port)
    {
        $this->dsn = 'host=' . $hostname . ' user=' . $username . ' password=' . $password . ' port=' . $port;
        return $this;
    }

    public function setDatabase($database)
    {
        if ($this->connection !== null) {
            return $this;
        }

        $resource = pg_connect($this->dsn . ' dbname=' . $database);

        if ($resource === false) {
            throw new Exception('Connect Error: ' . $this->dsn . ' dbname=' . $database);
        }
        $this->connection = $resource;

        return $this;
    }

    /**
     * @param $sql
     * @param array $params
     * @param int $queryType
     * @param \CCMBenchmark\Ting\Repository\Collection $collection
     * @return bool|int
     */
    public function execute(
        $sql,
        $params = array(),
        $queryType = QueryAbstract::TYPE_RESULT,
        Collection $collection = null
    ) {
        $paramsOrder = array();
        $sql = $this->convertParameters($sql, $paramsOrder);

        $values = array();
        foreach (array_keys($paramsOrder) as $key) {
            $values[] = &$params[$key];
        }

        $result = pg_query_params($this->connection, $sql, $values);

        return $this->setCollectionWithResult($result, $sql, $queryType, $collection);
    }

    /**
     * @param $resultResource
     * @param $query
     * @param $queryType
     * @param \CCMBenchmark\Ting\Repository\Collection $collection
     * @return bool|int
     * @throws \CCMBenchmark\Ting\Driver\QueryException
     */
    public function setCollectionWithResult($resultResource, $query, $queryType, Collection $collection = null)
    {
        if ($queryType !== QueryAbstract::TYPE_RESULT) {
            if ($queryType === QueryAbstract::TYPE_INSERT) {
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
        $result->setQuery($query);

        if ($collection === null) {
            $collection = new Collection();
        }

        $collection->set($result);
        return true;
    }

    /**
     * @param $sql
     * @param \Closure $callback
     * @param int $queryType
     * @param StatementInterface $statement
     * @return $this
     */
    public function prepare(
        $sql,
        \Closure $callback,
        $queryType = QueryAbstract::TYPE_RESULT,
        StatementInterface $statement = null
    ) {
        $paramsOrder = array();
        $sql = $this->convertParameters($sql, $paramsOrder);

        if ($statement === null) {
            $statement = new Statement();
        }

        $statementName = md5($sql);
        $result = pg_prepare($this->connection, $statementName, $sql);

        if ($result === false) {
            $this->ifIsError(function () use ($sql) {
                throw new QueryException(pg_last_error($this->connection) . ' (Query: ' . $sql . ')');
            });
        }

        $statement
            ->setConnection($this->connection)
            ->setQueryType($queryType);
        $statement->setQuery($sql);

        $callback($statement, $paramsOrder, $statementName);
        return $this;
    }

    /**
     * @param $sql
     * @param $paramsOrder
     * @return mixed
     */
    private function convertParameters($sql, &$paramsOrder)
    {
        $sql = preg_replace_callback(
            '/(?<!\\\):([a-zA-Z0-9_-]+)/',
            function ($match) use (&$paramsOrder) {
                $paramsOrder[$match[1]] = null;
                return '$' . count($paramsOrder);
            },
            $sql
        );

        $sql = str_replace('\:', ':', $sql);

        return $sql;
    }

    public function ifIsError(\Closure $callback)
    {
        $error = '';
        if ($this->connection !== null) {
            $error = pg_last_error($this->connection);
        }

        if ($error !== '') {
            $callback();
        }
    }

    public function ifIsNotConnected(\Closure $callback)
    {
        if ($this->connection === null) {
            $callback();
        }
    }

    public function escapeFields($fields, \Closure $callback)
    {
        foreach ($fields as &$field) {
            $field = '"' . $field . '"';
        }

        $callback($fields);
        return $this;
    }

    /**
     * @throws \CCMBenchmark\Ting\Driver\Exception
     */
    public function startTransaction()
    {
        if ($this->transactionOpened === true) {
            throw new Exception('Cannot start another transaction');
        }
        pg_query($this->connection, 'BEGIN');
        $this->transactionOpened = true;
    }

    /**
     * @throws \CCMBenchmark\Ting\Driver\Exception
     */
    public function commit()
    {
        if ($this->transactionOpened === false) {
            throw new Exception('Cannot commit no transaction');
        }
        pg_query($this->connection, 'COMMIT');
        $this->transactionOpened = false;
    }

    /**
     * @throws \CCMBenchmark\Ting\Driver\Exception
     */
    public function rollback()
    {
        if ($this->transactionOpened === false) {
            throw new Exception('Cannot rollback no transaction');
        }
        pg_query($this->connection, 'ROLLBACK');
        $this->transactionOpened = false;
    }
}
