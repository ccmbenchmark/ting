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
use CCMBenchmark\Ting\Query\QueryAbstract;
use CCMBenchmark\Ting\Repository\Collection;
use CCMBenchmark\Ting\Repository\CollectionInterface;

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

    public static function getConnectionKey(array $connectionConfig, $database)
    {
        return
            $connectionConfig['host'] . '|' .
            $connectionConfig['port'] . '|' .
            $connectionConfig['user'] . '|' .
            $connectionConfig['password'] . '|' .
            $database;
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

    public function execute($sql, array $params = array(), CollectionInterface $collection = null)
    {
        list ($sql, $paramsOrder) = $this->convertParameters($sql);

        $values = array();
        foreach (array_keys($paramsOrder) as $key) {
            if ($params[$key] instanceof \DateTime) {
                $params[$key] = $params[$key]->format('Y-m-d H:i:s');
            }
            $values[] = &$params[$key];
        }

        $result = pg_query_params($this->connection, $sql, $values);

        if ($collection === null) {
            return $result;
        }

        return $this->setCollectionWithResult($result, $sql, $collection);
    }

    protected function setCollectionWithResult($result, $sql, CollectionInterface $collection)
    {
        if ($result === false) {
            throw new QueryException($this->connection->error, $this->connection->errno);
        }

        $result = new Result($result);
        $result->setQuery($sql);
        $collection->set($result);

        return $collection;
    }

    public function prepare($sql)
    {
        list ($sql, $paramsOrder) = $this->convertParameters($sql);

        $statementName = sha1($sql);
        $statement     = new Statement($statementName, $paramsOrder);
        $result        = pg_prepare($this->connection, $statementName, $sql);

        if ($result === false) {
            $this->ifIsError(function () use ($sql) {
                throw new QueryException(pg_last_error($this->connection) . ' (Query: ' . $sql . ')');
            });
        }

        $statement
            ->setConnection($this->connection)
            ->setQuery($sql);

        return $statement;
    }

    /**
     * @param $sql
     * @return array
     */
    private function convertParameters($sql)
    {
        $i           = 0;
        $paramsOrder = [];

        $sql = preg_replace_callback(
            '/(?<!\\\):(#?[a-zA-Z0-9_-]+)/',
            function ($match) use (&$i, &$paramsOrder) {
                $paramsOrder[$match[1]] = null;
                return '$' . ++$i;
            },
            $sql
        );

        $sql = str_replace('\:', ':', $sql);

        return [$sql, $paramsOrder];
    }

    public function ifIsError(callable $callback)
    {
        $error = '';
        if ($this->connection !== null) {
            $error = pg_last_error($this->connection);
        }

        if ($error !== '') {
            $callback();
        }
    }

    public function ifIsNotConnected(callable $callback)
    {
        if ($this->connection === null) {
            $callback();
        }
    }

    public function escapeFields(array $fields, callable $callback)
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

    /**
     * @return int
     */
    public function getInsertId()
    {
        return (int) $this->connection->insert_id;
    }

    /**
     * @return int
     */
    public function getAffectedRows()
    {
        if ($this->connection->affected_rows < 0) {
            return 0;
        }

        return $this->connection->affected_rows;
    }
}
