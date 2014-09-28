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

use CCMBenchmark\Ting\Driver\DriverInterface;
use CCMBenchmark\Ting\Driver\Exception;
use CCMBenchmark\Ting\Driver\QueryException;
use CCMBenchmark\Ting\Driver\StatementInterface;
use CCMBenchmark\Ting\Query\QueryAbstract;
use CCMBenchmark\Ting\Repository\Collection;

class Driver implements DriverInterface
{

    /**
     * @var object driver
     */
    protected $driver = null;

    /**
     * @var \mysqli driver connection
     */
    protected $connection = null;

    /**
     * @var string
     */
    protected $currentDatabase = null;

    /**
     * @var bool
     */
    protected $connected = false;

    /**
     * @var bool
     */
    protected $transactionOpened = false;

    public function __construct($connection = null, $driver = null)
    {
        if ($connection === null) {
            $this->connection = \mysqli_init();
        } else {
            $this->connection = $connection;
        }

        if ($driver === null) {
            $this->driver = new \mysqli_driver();
        } else {
            $this->driver = $driver;
        }
    }

    public static function forConnectionKey($connectionConfig, $database, \Closure $callback)
    {
        $callback(
            $connectionConfig['host'] . '|' .
            $connectionConfig['port'] . '|' .
            $connectionConfig['user'] . '|' .
            $connectionConfig['password']
        );
    }

    /**
     * @throws \CCMBenchmark\Ting\Driver\Exception
     */
    public function connect($hostname, $username, $password, $port = 3306)
    {

        $this->driver->report_mode = MYSQLI_REPORT_STRICT;

        try {
            $this->connected = $this->connection->real_connect($hostname, $username, $password, null, $port);
        } catch (\Exception $e) {
            throw new Exception('Connect Error: ' . $e->getMessage(), $e->getCode());
        }

        return $this;
    }

    /**
     * @throws \CCMBenchmark\Ting\Driver\Exception
     */
    public function setDatabase($database)
    {

        if ($this->currentDatabase === $database) {
            return $this;
        }

        $this->connection->select_db($database);

        $this->ifIsError(function () {
            throw new Exception('Select database error: ' . $this->connection->error, $this->connection->errno);
        });

        $this->currentDatabase = $database;

        return $this;
    }

    public function ifIsError(\Closure $callback)
    {
        if ($this->connection->error !== '') {
            $callback($this->connection->error);
        }

        return $this;
    }

    public function execute(
        $sql,
        $params = array(),
        $queryType = QueryAbstract::TYPE_RESULT,
        Collection $collection = null
    ) {
        $sql = preg_replace_callback(
            '/(?<!\\\):(#?[a-zA-Z0-9_-]+)/',
            function ($match) use ($params) {
                if (!array_key_exists($match[1], $params)) {
                    throw new QueryException('Value has not been setted for param ' . $match[1]);
                }
                $value = $params[$match[1]];
                switch (gettype($value)) {
                    case "integer":
                        // integer and double doesn't need quotes
                    case "double":
                        return $value;
                        break;
                    default:
                        return '"' . $this->connection->real_escape_string($value) . '"';
                    break;
                }
            },
            $sql
        );

        $result = $this->connection->query($sql);

        return $this->setCollectionWithResult($result, $queryType, $collection);
    }


    public function setCollectionWithResult($result, $queryType, Collection $collection = null)
    {
        if ($queryType !== QueryAbstract::TYPE_RESULT) {
            if ($queryType === QueryAbstract::TYPE_INSERT) {
                return $this->connection->insert_id;
            }

            $result = $this->connection->affected_rows;

            if ($result === null || $result === -1) {
                return false;
            }
            return $result;
        }

        if ($result === false) {
            throw new QueryException($this->connection->error, $this->connection->errno);
        }

        if ($collection == null) {
            $collection = new Collection();
        }

        $collection->set(new Result($result));
        return true;
    }

    /**
     * @throws \CCMBenchmark\Ting\Driver\QueryException
     */
    public function prepare(
        $sql,
        \Closure $callback,
        $queryType = QueryAbstract::TYPE_RESULT,
        StatementInterface $statement = null
    ) {
        $sql = preg_replace_callback(
            '/(?<!\\\):(#?[a-zA-Z0-9_-]+)/',
            function ($match) use (&$paramsOrder) {
                $paramsOrder[$match[1]] = null;
                return '?';
            },
            $sql
        );

        $sql = str_replace('\:', ':', $sql);

        if ($statement === null) {
            $statement = new Statement();
        }

        $driverStatement = $this->connection->prepare($sql);

        if ($driverStatement === false) {
            $this->ifIsError(function () use ($sql) {
                throw new QueryException($this->connection->error . ' (Query: ' . $sql . ')', $this->connection->errno);
            });
        }

        $statement->setQueryType($queryType);

        $callback($statement, $paramsOrder, $driverStatement);

        return $this;
    }

    public function ifIsNotConnected(\Closure $callback)
    {
        if ($this->connected === false) {
            $callback();
        }

        return $this;
    }

    public function escapeFields($fields, \Closure $callback)
    {
        foreach ($fields as &$field) {
            $field = '`' . $field . '`';
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
        $this->connection->begin_transaction();
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
        $this->connection->commit();
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
        $this->connection->rollback();
        $this->transactionOpened = false;
    }

    public function isTransactionOpened()
    {
        return $this->transactionOpened;
    }
}
