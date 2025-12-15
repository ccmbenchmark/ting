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

use PgSql\Connection;
use CCMBenchmark\Ting\Driver\DriverInterface;
use CCMBenchmark\Ting\Driver\Exception;
use CCMBenchmark\Ting\Driver\NeverConnectedException;
use CCMBenchmark\Ting\Driver\QueryException;
use CCMBenchmark\Ting\Driver\StatementInterface;
use CCMBenchmark\Ting\Exceptions\DriverException;
use CCMBenchmark\Ting\Exceptions\StatementException;
use CCMBenchmark\Ting\Exceptions\TransactionException;
use CCMBenchmark\Ting\Logger\DriverLoggerInterface;
use CCMBenchmark\Ting\Repository\CollectionInterface;

class Driver implements DriverInterface
{
    /**
     * @var string
     */
    protected $name;

    protected string $database  = '';

    protected ?string $currentCharset = null;

    protected ?string $currentTimezone = null;

    /**
     * @var Connection|null
     */
    protected $connection = null;

    protected bool $transactionOpened = false;

    protected ?DriverLoggerInterface $logger = null;

    /**
     * spl_object_hash of current object
     */
    protected string $objectHash = '';

    /**
     * @var \PgSql\Result|null
     */
    protected $result = null;

    /**
     * @var array<string, StatementInterface>
     */
    protected array $preparedQueries = [];

    /**
     * @var string
     */
    protected $dsn;

    public static function getConnectionKey(array $connectionConfig, string $database): string
    {
        return
            $connectionConfig['host'] . '|' .
            $connectionConfig['port'] . '|' .
            $connectionConfig['user'] . '|' .
            $connectionConfig['password'] . '|' .
            $database;
    }

    /**
     * Construct connection information
     */
    public function connect(string $hostname, string $username, string $password, int $port): static
    {
        $this->dsn = 'host=' . $hostname . ' user=' . $username . ' password=' . $password . ' port=' . $port;
        return $this;
    }

    /**
     * Close the connection to the database
     * @return $this
     */
    public function close(): static
    {
        if ($this->connection !== null) {
            pg_close($this->connection);
            $this->connection = null;
        }

        return $this;
    }

    /**
     * @throws DriverException
     */
    public function setCharset(string $charset): void
    {
        if ($this->currentCharset === $charset) {
            return;
        }

        if ($this->connection === null || pg_set_client_encoding($this->connection, $charset) === -1) {
            throw new DriverException('Can\'t set charset ' . $charset . ' (' . pg_last_error($this->connection) . ')');
        }

        $this->currentCharset = $charset;
    }

    public function setName(string $name): static
    {
        $this->name = $name;

        return $this;
    }

    /**
     * Connect the driver to the given database
     * @throws DriverException
     */
    public function setDatabase(string $database): static
    {
        if ($this->connection !== null) {
            return $this;
        }

        $resource = pg_connect($this->dsn . ' dbname=' . $database);
        $this->database = $database;

        if ($resource === false) {
            $dsn = preg_replace('/((user|password)=[^\s]+)/', '$2=<REDACTED>', $this->dsn);
            throw new DriverException('Connect Error: ' . $dsn . ' dbname=' . $database);
        }
        $this->connection = $resource;

        return $this;
    }

    public function setLogger(?DriverLoggerInterface $logger = null): static
    {
        $this->logger = $logger;
        $this->objectHash = spl_object_hash($this);

        return $this;
    }


    /**
     * Execute the given query on the actual connection
     * @throws QueryException
     */
    public function execute(string $sql, array $params = [], ?CollectionInterface $collection = null): string|int|bool|array|CollectionInterface|null
    {
        [$sql, $paramsOrder] = $this->convertParameters($sql);

        $this->validateConnection();

        $values = [];
        foreach (array_keys($paramsOrder) as $key) {
            $values[] = &$params[$key];
        }

        if ($this->logger !== null) {
            $this->logger->startQuery($sql, $params, $this->objectHash, $this->database);
        }

        if ($values === []) {
            $result = pg_query($this->connection, $sql);
            if ($result === false) {
                throw new QueryException(pg_last_error($this->connection) . ' (Query: ' . $sql . ')');
            }
            $this->result = $result;
        } else {
            $result = pg_query_params($this->connection, $sql, $values);
            if ($result === false) {
                throw new QueryException(pg_last_error($this->connection) . ' (Query: ' . $sql . ')');
            }
            $this->result = $result;
        }

        if ($this->logger !== null) {
            $this->logger->stopQuery();
        }


        if (!$collection instanceof CollectionInterface) {
            $resultStatus = pg_result_status($this->result);
            if ($resultStatus === \PGSQL_TUPLES_OK) {
                return pg_fetch_assoc($this->result);
            }
            return $resultStatus;
        }

        return $this->setCollectionWithResult($sql, $collection);
    }

    /**
     * @param string $sql
     * @param CollectionInterface $collection
     * @return CollectionInterface
     * @throws QueryException
     *
     * @internal
     */
    protected function setCollectionWithResult($sql, CollectionInterface $collection): CollectionInterface
    {
        $result = new Result();
        $result->setConnectionName($this->name);
        $result->setDatabase($this->database);
        $result->setResult($this->result);
        $result->setQuery($sql);
        $collection->set($result);

        return $collection;
    }

    /**
     * Prepare the given query against the current connection
     * @param string $originalSQL
     * @return Statement|StatementInterface
     * @throws QueryException
     */
    public function prepare(string $originalSQL): StatementInterface
    {
        [$sql, $paramsOrder] = $this->convertParameters($originalSQL);

        $statementName = sha1($originalSQL);

        if (isset($this->preparedQueries[$statementName])) {
            return $this->preparedQueries[$statementName];
        }

        $statement = new Statement($statementName, $paramsOrder, $this->name, $this->database);

        if ($this->logger !== null) {
            $this->logger->startPrepare($originalSQL, $this->objectHash, $this->database);
            $statement->setLogger($this->logger);
        }
        $this->validateConnection();
        $result = pg_prepare($this->connection, $statementName, $sql);
        if ($this->logger !== null) {
            $this->logger->stopPrepare($statementName);
        }

        if ($result === false) {
            $this->ifIsError(function () use ($sql): void {
                throw new QueryException(pg_last_error($this->connection) . ' (Query: ' . $sql . ')');
            });
        }

        $statement
            ->setConnection($this->connection)
            ->setQuery($sql);

        $this->preparedQueries[$statementName] = $statement;

        return $statement;
    }

    /**
     * @return array
     */
    private function convertParameters(string $sql): array
    {
        $i           = 1;
        $paramsOrder = [];

        /**
         * Match : values (:name)
         * Don't match : values (\:name)
         * Don't match : HH:MI:SS
         * Don't match : ::string
         */
        $sql = preg_replace_callback(
            '/(?<!\b)(?<![:\\\]):(#?[a-zA-Z0-9_-]+)/',
            function (array $match) use (&$i, &$paramsOrder): string {
                if (isset($paramsOrder[$match[1]]) === false) {
                    $paramsOrder[$match[1]] = $i++;
                }

                return '$' . $paramsOrder[$match[1]];
            },
            (string) $sql
        );

        $sql = str_replace('\:', ':', $sql);

        return [$sql, $paramsOrder];
    }

    /**
     * Execute callback if an error has been encountered
     * @param callable $callback
     */
    public function ifIsError(callable $callback): static
    {
        $error = '';
        if ($this->connection !== null) {
            $error = pg_last_error($this->connection);
        }

        if ($error !== '') {
            $callback();
        }

        return $this;
    }

    /**
     * Execute the callback if the driver is not connected
     * @param callable $callback
     */
    public function ifIsNotConnected(callable $callback): static
    {
        if ($this->connection === null) {
            $callback();
        }

        return $this;
    }

    /**
     * Escape the given field name according to PGSQL Standards
     */
    public function escapeField(mixed $field = null): string
    {
        return '"' . $field . '"';
    }

    /**
     * Start a transaction against the current connection
     * @throws TransactionException
     */
    public function startTransaction(): void
    {
        if ($this->transactionOpened === true) {
            throw new TransactionException('Cannot start another transaction');
        }
        $this->validateConnection();
        pg_query($this->connection, 'BEGIN');
        $this->transactionOpened = true;
    }

    /**
     * Commit the transaction against the current connection
     * @throws TransactionException
     */
    public function commit(): void
    {
        if ($this->transactionOpened === false) {
            throw new TransactionException('Cannot commit no transaction');
        }
        $this->validateConnection();
        pg_query($this->connection, 'COMMIT');
        $this->transactionOpened = false;
    }

    /**
     * Rollback the actual opened transaction
     * @throws TransactionException
     */
    public function rollback(): void
    {
        if ($this->transactionOpened === false) {
            throw new TransactionException('Cannot rollback no transaction');
        }
        $this->validateConnection();
        pg_query($this->connection, 'ROLLBACK');
        $this->transactionOpened = false;
    }

    /**
     * Return the last inserted id
     * @return int
     */
    public function getInsertedId(): int
    {
        $this->validateConnection();
        $resultResource = pg_query($this->connection, 'SELECT lastval()');
        if ($resultResource === false) {
            throw new DriverException('Could not fetch last inserted id.');
        }
        $row = pg_fetch_row($resultResource);
        if ($row === false) {
            throw new DriverException('Could not fetch last inserted id.');
        }
        return (int) $row[0];
    }

    /**
     * Return the last inserted id for a sequence
     * @throws Exception
     */
    public function getInsertedIdForSequence(string $sequenceName): int
    {
        $this->validateConnection();
        $sql = "SELECT currval($1)";
        $resultResource = @pg_query_params($this->connection, $sql, [$sequenceName]);

        if ($resultResource === false) {
            throw new QueryException(pg_last_error($this->connection) . ' (Query: ' . $sql . ')');
        }

        $row = pg_fetch_row($resultResource);
        if ($row === false) {
            throw new QueryException('Could not fetch last inserted id. Details: '. pg_last_error($this->connection));
        }
        return (int) $row[0];
    }

    /**
     * Give the number of affected rows
     */
    public function getAffectedRows(): int
    {
        if ($this->result === null) {
            return 0;
        }

        return pg_affected_rows($this->result);
    }

    /**
     * @param $statement
     * @throws StatementException
     */
    public function closeStatement(string $statement): void
    {
        if (isset($this->preparedQueries[$statement]) === false) {
            throw new StatementException('Cannot close non prepared statement');
        }
        unset($this->preparedQueries[$statement]);
    }

    /**
     * @return bool true on success, false on failure
     * @throws NeverConnectedException when you have not been connected to your database before trying to pint it.
     */
    public function ping(): bool
    {
        $this->validateConnection();

        $result = pg_ping($this->connection);

        if ($result && $this->currentCharset !== null) {
            pg_set_client_encoding($this->connection, $this->currentCharset);
        }
        if ($result && $this->currentTimezone !== null) {
            pg_query($this->connection, sprintf('SET timezone = "%s";', $this->currentTimezone));
        }

        return $result;
    }

    public function setTimezone(?string $timezone = null): void
    {
        if ($this->currentTimezone === $timezone) {
            return;
        }
        $value = $timezone;
        $query = 'SET timezone = "%s";';
        if ($timezone === null) {
            $value = 'DEFAULT';
            $query = str_replace('"', '', $query);
        }
        $this->validateConnection();
        pg_query($this->connection, sprintf($query, $value));
        $this->currentTimezone = $timezone;
    }

    public function reconnect(): bool
    {
        $this->connection = null;
        try {
            $this->setDatabase($this->database);
            if ($this->currentTimezone !== null) {
                $tz = $this->currentTimezone;
                $this->currentTimezone = null;
                $this->setTimezone($tz);
            }
            if ($this->currentCharset !== null) {
                $charset = $this->currentCharset;
                $this->currentCharset = null;
                $this->setCharset($charset);
            }
        } catch (DriverException) {
            return false;
        }

        return true;
    }

    /**
     * @throws NeverConnectedException
     */
    private function validateConnection(): void
    {
        if ($this->connection === null) {
            throw new NeverConnectedException('Please connect to your database before trying to ping it.');
        }
    }
}
