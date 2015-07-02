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
use CCMBenchmark\Ting\Logger\DriverLoggerInterface;
use CCMBenchmark\Ting\Repository\CollectionInterface;

class Driver implements DriverInterface
{

    /**
     * @var \mysqli_driver|Object|null driver
     */
    protected $driver = null;

    /**
     * @var \mysqli|null driver connection
     */
    protected $connection = null;

    /**
     * @var string|null
     */
    protected $currentDatabase = null;

    /**
     * @var string|null
     */
    protected $currentCharset = null;

    /**
     * @var bool
     */
    protected $connected = false;

    /**
     * @var bool
     */
    protected $transactionOpened = false;

    /**
     * @var DriverLoggerInterface|null
     */
    protected $logger = null;

    /**
     * @var string hash of current object
     */
    protected $objectHash = '';

    /**
     * @var array List of already prepared queries
     */
    protected $preparedQueries = array();

    /**
     * @param  \mysqli|Object|null $connection
     * @param \mysqli_driver|Object|null $driver
     */
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

    /**
     * @param array $connectionConfig
     * @param string $database
     * @return string
     */
    public static function getConnectionKey(array $connectionConfig, $database)
    {
        return
            $connectionConfig['host'] . '|' .
            $connectionConfig['port'] . '|' .
            $connectionConfig['user'] . '|' .
            $connectionConfig['password'];
    }

    /**
     * @param string $hostname
     * @param string $username
     * @param string $password
     * @param int $port
     * @return $this
     * @throws Exception
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
     * Close the connection to the database
     * @return $this
     */
    public function close()
    {
        if ($this->connected) {
            $this->connection->close();
            $this->connected = false;
        }

        return $this;
    }

    /**
     * @param string $charset
     * @return void
     * @throws Exception
     */
    public function setCharset($charset)
    {
        if ($this->currentCharset === $charset) {
            return $this;
        }

        if ($this->connection->set_charset($charset) === false) {
            throw new Exception('Can\'t set charset ' . $charset . ' (' . $this->connection->error . ')');
        }
        $this->currentCharset = $charset;
    }

    /**
     * @param DriverLoggerInterface $logger
     */
    public function setLogger(DriverLoggerInterface $logger = null)
    {
        $this->logger = $logger;
        $this->objectHash = spl_object_hash($this);
    }


    /**
     * @param string $database
     * @return $this
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

    /**
     * @param callable $callback
     * @return $this
     */
    public function ifIsError(callable $callback)
    {
        if ($this->connection->error !== '') {
            $callback($this->connection->error);
        }

        return $this;
    }

    /**
     * @param string $sql
     * @param array $params
     * @param CollectionInterface $collection
     * @return mixed|CollectionInterface
     * @throws QueryException
     */
    public function execute($sql, array $params = array(), CollectionInterface $collection = null)
    {
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


        if ($this->logger !== null) {
            $this->logger->startQuery($sql, $params, $this->objectHash, $this->currentDatabase);
        }

        $result = $this->connection->query($sql);

        if ($this->logger !== null) {
            $this->logger->stopQuery();
        }

        if ($result === false) {
            throw new QueryException($this->connection->error, $this->connection->errno);
        }

        if ($collection === null) {
            if ($result === true) {
                return true;
            }

            return $result->fetch_assoc();
        }

        return $this->setCollectionWithResult($result, $collection);
    }

    /**
     * @param \mysqli_result|Object $result
     * @param CollectionInterface $collection
     * @return CollectionInterface
     * @throws QueryException
     */
    protected function setCollectionWithResult($result, CollectionInterface $collection)
    {
        $collection->set(new Result($result));

        return $collection;
    }

    /**
     * @param string $sql
     * @return \CCMBenchmark\Ting\Driver\StatementInterface
     */
    public function prepare($sql)
    {

        $statementName = sha1($sql);
        if (isset($this->preparedQueries[$statementName])) {
            return $this->preparedQueries[$statementName];
        }
        $paramsOrder = [];
        $sql = preg_replace_callback(
            '/(?<!\\\):(#?[a-zA-Z0-9_-]+)/',
            function ($match) use (&$paramsOrder) {
                $paramsOrder[$match[1]] = null;
                return '?';
            },
            $sql
        );

        $sql = str_replace('\:', ':', $sql);

        if ($this->logger !== null) {
            $this->logger->startPrepare($sql, $this->objectHash, $this->currentDatabase);
        }
        $driverStatement = $this->connection->prepare($sql);

        if ($driverStatement === false) {
            $this->ifIsError(function () use ($sql) {
                throw new QueryException($this->connection->error . ' (Query: ' . $sql . ')', $this->connection->errno);
            });
        }

        if ($this->logger !== null) {
            $this->logger->stopPrepare(spl_object_hash($driverStatement));
        }

        $statement = new Statement($driverStatement, $paramsOrder);
        $statement->setLogger($this->logger);

        $this->preparedQueries[$statementName] = $statement;


        return $statement;
    }

    /**
     * @param callable $callback
     * @return $this
     */
    public function ifIsNotConnected(callable $callback)
    {
        if ($this->connected === false) {
            $callback();
        }

        return $this;
    }

    /**
     * @param $field
     * @return string
     */
    public function escapeField($field)
    {
        return '`' . $field . '`';
    }

    /**
     * @throws Exception
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
     * @throws Exception
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
     * @throws Exception
     */
    public function rollback()
    {
        if ($this->transactionOpened === false) {
            throw new Exception('Cannot rollback no transaction');
        }
        $this->connection->rollback();
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

    /**
     * @param $statement
     * @throws Exception
     */
    public function closeStatement($statement)
    {
        if (isset($this->preparedQueries[$statement]) === false) {
            throw new Exception('Cannot close non prepared statement');
        }
        unset($this->preparedQueries[$statement]);
    }
}
