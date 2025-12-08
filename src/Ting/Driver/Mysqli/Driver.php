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

use mysqli;
use mysqli_driver;
use Exception;
use mysqli_result;
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
    protected string $name;

    /**
     * @var mysqli_driver|null $driver
     */
    protected $driver;

    /**
     * @var mysqli|null driver connection
     */
    protected $connection = null;

    protected ?string $currentDatabase = null;

    protected ?string $currentCharset = null;

    protected ?string $currentTimezone = null;

    protected bool $connected = false;

    protected bool $transactionOpened = false;

    protected ?DriverLoggerInterface $logger = null;

    /**
     * hash of current object
     */
    protected string $objectHash = '';

    /**
     * @var array<string,StatementInterface> List of already prepared queries
     */
    protected array $preparedQueries = [];

    /**
     * @var array<string,StatementInterface> Old list of prepared queries, filled after a reconnect
     */
    protected array $oldPreparedQueries = [];

    /**
     * Match parameter in SQL
     *
     * Match : values (:name)
     * Don't match : values (\:name)
     * Don't match : HH:MI:SS
     * Don't match : ::string
     */
    private string $parameterMatching = '(?<!\b)(?<![:\\\]):(#?[a-zA-Z0-9_-]+)';

    /**
     * Data used to open a connection.
     */
    private array $connectionConfig = [];

    /**
     * @param mysqli|null $connection
     * @param mysqli_driver|null $driver
     */
    public function __construct($connection = null, $driver = null)
    {
        if ($connection === null) {
            $this->createConnection();
        } else {
            $this->connection = $connection;
        }

        $this->driver = $driver ?? new mysqli_driver();
    }

    /**
     * @param array $connectionConfig
     * @param string $database
     */
    public static function getConnectionKey(array $connectionConfig, $database): string
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
     * @throws ConnectionException
     */
    public function connect($hostname, $username, $password, $port = 3306): static
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
        } catch (Exception $e) {
            throw new ConnectionException('Connect Error: ' . $e->getMessage(), $e->getCode());
        }

        return $this;
    }

    /**
     * Close the connection to the database
     */
    public function close(): static
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
    public function setCharset($charset): void
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
    public function setLogger(?DriverLoggerInterface $logger = null): static
    {
        $this->logger = $logger;
        $this->objectHash = spl_object_hash($this);

        return $this;
    }

    /**
     * @param string $name
     * @return $this
     */
    public function setName($name): static
    {
        $this->name = (string) $name;

        return $this;
    }

    /**
     * @param string $database
     * @return $this
     * @throws DatabaseException
     */
    public function setDatabase($database): static
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
    public function ifIsError(callable $callback): static
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
     * @throws QueryException
     */
    public function execute(string $sql, array $params = [], ?CollectionInterface $collection = null): bool|CollectionInterface|array
    {
        $sql = preg_replace_callback(
            '/' . $this->parameterMatching . '/',
            function (array $match) use ($params) {
                if (!\array_key_exists($match[1], $params)) {
                    throw new QueryException('Value has not been set for param ' . $match[1]);
                }

                return (string) $this->quoteValue($params[$match[1]]);
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

        if ($result === true) {
            return true;
        }

        if ($collection === null) {
            return $result->fetch_assoc();
        }

        return $this->setCollectionWithResult($result, $collection);
    }

    /**
     * Quote value according to the type of variable
     * @param mixed $value
     */
    protected function quoteValue($value): string | int | float
    {
        return match (\gettype($value)) {
            "boolean" => (int) $value,
            "integer", "double" => $value,
            "NULL" => 'null',
            default => '"' . $this->connection->real_escape_string($value) . '"',
        };
    }

    /**
     * @param mysqli_result $resultData
     * @param CollectionInterface $collection
     * @return CollectionInterface
     */
    protected function setCollectionWithResult($resultData, CollectionInterface $collection): CollectionInterface
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
     * @return StatementInterface
     * @throws QueryException
     */
    public function prepare(string $sql): StatementInterface
    {
        $statementName = sha1($sql);
        if (isset($this->preparedQueries[$statementName])) {
            return $this->preparedQueries[$statementName];
        }
        $paramsOrder = [];
        $sql = preg_replace_callback(
            '/' . $this->parameterMatching . '/',
            function (array $match) use (&$paramsOrder): string {
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
            throw new QueryException($this->connection->error . ' (Query: ' . $sql . ')', $this->connection->errno);
//            $this->ifIsError(function () use ($sql): void {
//
//            });
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
    public function ifIsNotConnected(callable $callback): static
    {
        if ($this->connected === false) {
            $callback();
        }

        return $this;
    }

    public function escapeField(mixed $field = null): string
    {
        return '`' . $field . '`';
    }

    /**
     * @throws TransactionException
     */
    public function startTransaction(): void
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
    public function commit(): void
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
    public function rollback(): void
    {
        if ($this->transactionOpened === false) {
            throw new TransactionException('Cannot rollback no transaction');
        }
        $this->connection->rollback();
        $this->transactionOpened = false;
    }

    public function getInsertedId(): int
    {
        return (int) $this->connection->insert_id;
    }

    public function getAffectedRows(): int|string
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
    public function closeStatement(string $statement): void
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
    public function ping(): bool
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

        return $this->reconnect();
    }

    public function setTimezone(?string $timezone = null): void
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
        $connection = mysqli_init();
        if ($connection instanceof \mysqli) {
            $this->connection = $connection;
            $this->connection->options(MYSQLI_OPT_INT_AND_FLOAT_NATIVE, 1);
        }
    }

    public function reconnect(): bool
    {
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
}
