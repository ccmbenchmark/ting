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

use CCMBenchmark\Ting\Driver\StatementInterface;
use CCMBenchmark\Ting\Exceptions\ConnectionException;
use CCMBenchmark\Ting\Exceptions\DatabaseException;
use CCMBenchmark\Ting\Exceptions\DriverException;
use CCMBenchmark\Ting\Driver\DriverInterface;
use CCMBenchmark\Ting\Driver\NeverConnectedException;
use CCMBenchmark\Ting\Driver\QueryException;
use CCMBenchmark\Ting\Exceptions\StatementException;
use CCMBenchmark\Ting\Exceptions\TransactionException;
use CCMBenchmark\Ting\Logger\DriverLoggerInterface;
use CCMBenchmark\Ting\Repository\CollectionInterface;

use mysqli_sql_exception;

class Driver implements DriverInterface
{
    /**
     * @var string
     */
    protected $name;

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
     * @var string|null
     */
    protected $currentTimezone = null;

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
     * @var array<string,StatementInterface> List of already prepared queries
     */
    protected $preparedQueries = [];

    /**
     * @var array<string,StatementInterface> Old list of prepared queries, filled after a reconnect
     */
    protected $oldPreparedQueries = [];

    /**
     * @var string Match parameter in SQL
     *
     * Match : values (:name)
     * Don't match : values (\:name)
     * Don't match : HH:MI:SS
     * Don't match : ::string
     */
    private $parameterMatching = '(?<!\b)(?<![:\\\]):(#?[a-zA-Z0-9_-]+)';

    /**
     * Data used to open a connection.
     *
     * @var array
     */
    private $connectionConfig = [];

    /**
     * @param  \mysqli|Object|null $connection
     * @param \mysqli_driver|Object|null $driver
     */
    public function __construct($connection = null, $driver = null)
    {
        if ($connection === null) {
            $this->createConnection();
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
     *
     * @return $this
     *
     * @throws ConnectionException
     */
    public function connect($hostname, $username, $password, $port = 3306)
    {
        $this->driver->report_mode = MYSQLI_REPORT_STRICT;

        $this->connectionConfig = [
            'hostname' => $hostname,
            'username' => $username,
            'password' => $password,
            'port' => $port
        ];

        try {
            $this->connected = $this->connection->real_connect($hostname, $username, $password, null, $port);
        } catch (\Exception $e) {
            throw new ConnectionException('Connect Error: ' . $e->getMessage(), $e->getCode());
        }

        return $this;
    }

    /**
     * Close the connection to the database
     * @return $this
     */
    public function close()
    {
        if ($this->connected === true) {
            $this->connection->close();
            $this->connected = false;
        }

        return $this;
    }

    /**
     * @param string $charset
     * @return void
     * @throws DriverException
     */
    public function setCharset($charset)
    {
        if ($this->currentCharset === $charset) {
            return;
        }

        if ($this->connection->set_charset($charset) === false) {
            throw new DriverException('Can\'t set charset ' . $charset . ' (' . $this->connection->error . ')');
        }
        $this->currentCharset = $charset;
    }

    /**
     * @param DriverLoggerInterface $logger
     */
    public function setLogger(?DriverLoggerInterface $logger = null)
    {
        $this->logger = $logger;
        $this->objectHash = spl_object_hash($this);
    }

    /**
     * @param string $name
     * @return $this
     */
    public function setName($name)
    {
        $this->name = (string) $name;

        return $this;
    }

    /**
     * @param string $database
     * @return $this
     * @throws DatabaseException
     */
    public function setDatabase($database)
    {
        if ($this->currentDatabase === $database) {
            return $this;
        }

        $this->connection->select_db($database);

        $this->ifIsError(function (): void {
            throw new DatabaseException('Select database error: ' . $this->connection->error, $this->connection->errno);
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
    public function execute($sql, array $params = [], ?CollectionInterface $collection = null)
    {
        $sql = preg_replace_callback(
            '/' . $this->parameterMatching . '/',
            function ($match) use ($params) {
                if (!\array_key_exists($match[1], $params)) {
                    throw new QueryException('Value has not been set for param ' . $match[1]);
                }

                return $this->quoteValue($params[$match[1]]);
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
            throw new QueryException($this->connection->error . ' (Query: ' . $sql . ')', $this->connection->errno);
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
     * Quote value according to the type of variable
     * @param mixed $value
     * @return mixed
     */
    protected function quoteValue($value)
    {
        return match (\gettype($value)) {
            "boolean" => (int) $value,
            "integer", "double" => $value,
            "NULL" => 'null',
            default => '"' . $this->connection->real_escape_string($value) . '"',
        };
    }

    /**
     * @param \mysqli_result|Object $resultData
     * @param CollectionInterface $collection
     * @return CollectionInterface
     */
    protected function setCollectionWithResult($resultData, CollectionInterface $collection)
    {
        $result = new Result();
        $result->setConnectionName($this->name);
        $result->setDatabase($this->currentDatabase);
        $result->setResult($resultData);
        $collection->set($result);

        return $collection;
    }

    /**
     * @param string $sql
     * @return \CCMBenchmark\Ting\Driver\StatementInterface
     * @throws QueryException
     */
    public function prepare($sql)
    {
        $statementName = sha1($sql);
        if (isset($this->preparedQueries[$statementName])) {
            return $this->preparedQueries[$statementName];
        }
        $paramsOrder = [];
        $sql = preg_replace_callback(
            '/' . $this->parameterMatching . '/',
            function ($match) use (&$paramsOrder) {
                $paramsOrder[] = $match[1];
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
            $this->ifIsError(function () use ($sql): void {
                throw new QueryException($this->connection->error . ' (Query: ' . $sql . ')', $this->connection->errno);
            });
        }

        if ($this->logger !== null) {
            $this->logger->stopPrepare(spl_object_hash($driverStatement));
        }

        $statement = new Statement($driverStatement, $paramsOrder, $this->name, $this->currentDatabase);
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
     * @throws TransactionException
     */
    public function startTransaction()
    {
        if ($this->transactionOpened === true) {
            throw new TransactionException('Cannot start another transaction');
        }
        $this->connection->begin_transaction();
        $this->transactionOpened = true;
    }

    /**
     * @throws TransactionException
     */
    public function commit()
    {
        if ($this->transactionOpened === false) {
            throw new TransactionException('Cannot commit no transaction');
        }
        $this->connection->commit();
        $this->transactionOpened = false;
    }

    /**
     * @throws TransactionException
     */
    public function rollback()
    {
        if ($this->transactionOpened === false) {
            throw new TransactionException('Cannot rollback no transaction');
        }
        $this->connection->rollback();
        $this->transactionOpened = false;
    }

    /**
     * @return int
     */
    public function getInsertedId()
    {
        return (int) $this->connection->insert_id;
    }

    /**
     * @deprecated
     */
    public function getInsertId()
    {
        error_log(sprintf('%s::getInsertId() method is deprecated as of version 3.8 of Ting and will be removed in 4.0. Use %s::getInsertedId() instead.', self::class, self::class), E_USER_DEPRECATED);

        return $this->getInsertedId();
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
     * @throws StatementException
     */
    public function closeStatement($statement)
    {
        if (!isset($this->preparedQueries[$statement]) && !isset($this->oldPreparedQueries[$statement])) {
            throw new StatementException('Cannot close non prepared statement');
        }
        unset($this->preparedQueries[$statement], $this->oldPreparedQueries[$statement]);
    }

    /**
     * Ping server and reconnect if connection has been lost.
     *
     * @return bool true on success, false on failure
     *
     * @throws NeverConnectedException when you have not been connected to your database before trying to ping it.
     */
    public function ping()
    {
        if ($this->connected === false) {
            throw new NeverConnectedException('Please connect to your database before trying to ping it.');
        }

        // mysqli.reconnect has been removed in PHP 8.2 and mysqli_ping has been deprecated in PHP 8.4 as it has no effect, so we cannot rely on ping
        // We need to reimplement the logic here.

        // First try a simple query, if it works we don't need to do anything
        try {
            $result = $this->connection->query('SELECT 1');
            if ($result !== false) {
                return true;
            }
        } catch (mysqli_sql_exception) { }

        try {
            $this->createConnection();
            $this->connected = $this->connection->real_connect($this->connectionConfig['hostname'], $this->connectionConfig['username'], $this->connectionConfig['password'], $this->currentDatabase, $this->connectionConfig['port']);

            if ($this->currentCharset !== null) {
                $this->connection->set_charset($this->currentCharset);
            }

            if ($this->currentTimezone !== null) {
                $this->connection->query(sprintf('SET time_zone = "%s";', $this->currentTimezone));
            }
            $this->oldPreparedQueries = array_merge($this->preparedQueries, $this->oldPreparedQueries);
            $this->preparedQueries = [];
            return true;
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * @param $timezone
     */
    public function setTimezone($timezone)
    {
        if ($this->currentTimezone === $timezone) {
            return;
        }

        $value = $timezone;
        $query = 'SET time_zone = "%s";';
        if ($timezone === null) {
            $value = 'DEFAULT';
            $query = str_replace('"', '', $query);
        }
        $this->connection->query(sprintf($query, $value));
        $this->currentTimezone = $timezone;
    }

    private function createConnection(): void
    {
        $this->connection = mysqli_init();
        $this->connection->options(MYSQLI_OPT_INT_AND_FLOAT_NATIVE, 1);
    }
}
