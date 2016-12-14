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
use CCMBenchmark\Ting\Driver\NeverConnectedException;
use CCMBenchmark\Ting\Driver\QueryException;
use CCMBenchmark\Ting\Logger\DriverLoggerInterface;
use CCMBenchmark\Ting\Repository\CollectionInterface;

class Driver implements DriverInterface
{

    /**
     * @var string
     */
    protected $name;

    /**
     * @var string current database name
     */
    protected $database  = '';

    /**
     * @var string|null
     */
    protected $currentCharset = null;

    /**
     * @var resource pgsql
     */
    protected $connection = null;

    /**
     * @var bool
     */
    protected $transactionOpened = false;

    /**
     * @var DriverLoggerInterface|null
     */
    protected $logger = null;

    /**
     * @var string spl_object_hash of current object
     */
    protected $objectHash = '';

    /**
     * @var resource
     */
    protected $result = null;

    /**
     * @var array List of already prepared queries
     */
    protected $preparedQueries = array();

    /**
     * Return a unique connection key identifier
     * @param array  $connectionConfig
     * @param string $database
     * @return string
     */
    public static function getConnectionKey(array $connectionConfig, $database)
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
     * @param string $hostname
     * @param string $username
     * @param string $password
     * @param int    $port
     * @return $this
     */
    public function connect($hostname, $username, $password, $port)
    {
        $this->dsn = 'host=' . $hostname . ' user=' . $username . ' password=' . $password . ' port=' . $port;
        return $this;
    }

    /**
     * Close the connection to the database
     * @return $this
     */
    public function close()
    {
        if ($this->connection !== null) {
            pg_close($this->connection);
            $this->connection = null;
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
            return;
        }

        if (pg_set_client_encoding($this->connection, $charset) === -1) {
            throw new Exception('Can\'t set charset ' . $charset . ' (' . pg_last_error($this->connection) . ')');
        }

        $this->currentCharset = $charset;
    }

    /**
     * @param string $name
     * @return $this
     */
    public function setName($name)
    {
        $this->name = $name;

        return $this;
    }

    /**
     * Connect the driver to the given database
     * @param string $database
     * @return $this
     * @throws Exception
     */
    public function setDatabase($database)
    {
        if ($this->connection !== null) {
            return $this;
        }

        $resource = pg_connect($this->dsn . ' dbname=' . $database);
        $this->database = $database;

        if ($resource === false) {
            throw new Exception('Connect Error: ' . $this->dsn . ' dbname=' . $database);
        }
        $this->connection = $resource;

        return $this;
    }

    public function setLogger(DriverLoggerInterface $logger = null)
    {
        $this->logger = $logger;
        $this->objectHash = spl_object_hash($this);
    }


    /**
     * Execute the given query on the actual connection
     * @param string              $originalSQL
     * @param array               $params
     * @param CollectionInterface $collection
     * @return CollectionInterface|mixed|resource
     * @throws QueryException
     */
    public function execute($originalSQL, array $params = array(), CollectionInterface $collection = null)
    {
        list ($sql, $paramsOrder) = $this->convertParameters($originalSQL);

        $values = array();
        foreach (array_keys($paramsOrder) as $key) {
            $values[] = &$params[$key];
        }

        if ($this->logger !== null) {
            $this->logger->startQuery($originalSQL, $params, $this->objectHash, $this->database);
        }

        if ($values === []) {
            $this->result = pg_query($this->connection, $sql);
        } else {
            $this->result = pg_query_params($this->connection, $sql, $values);
        }

        if ($this->logger !== null) {
            $this->logger->stopQuery();
        }

        if ($this->result === false) {
            throw new QueryException(pg_last_error($this->connection));
        }

        if ($collection === null) {
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
    protected function setCollectionWithResult($sql, CollectionInterface $collection)
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
     * @return Statement|\CCMBenchmark\Ting\Driver\StatementInterface
     * @throws QueryException
     */
    public function prepare($originalSQL)
    {
        list ($sql, $paramsOrder) = $this->convertParameters($originalSQL);

        $statementName = sha1($originalSQL);

        if (isset($this->preparedQueries[$statementName]) === true) {
            return $this->preparedQueries[$statementName];
        }

        $statement = new Statement($statementName, $paramsOrder, $this->name, $this->database);

        if ($this->logger !== null) {
            $this->logger->startPrepare($originalSQL, $this->objectHash, $this->database);
            $statement->setLogger($this->logger);
        }
        $result = pg_prepare($this->connection, $statementName, $sql);
        if ($this->logger !== null) {
            $this->logger->stopPrepare($statementName);
        }

        if ($result === false) {
            $this->ifIsError(function () use ($sql) {
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
     * @param $sql
     * @return array
     */
    private function convertParameters($sql)
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
            function ($match) use (&$i, &$paramsOrder) {
                if (isset($paramsOrder[$match[1]]) === false) {
                    $paramsOrder[$match[1]] = $i++;
                }

                return '$' . $paramsOrder[$match[1]];
            },
            $sql
        );

        $sql = str_replace('\:', ':', $sql);

        return [$sql, $paramsOrder];
    }

    /**
     * Execute callback if an error has been encountered
     * @param callable $callback
     */
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

    /**
     * Execute the callback if the driver is not connected
     * @param callable $callback
     */
    public function ifIsNotConnected(callable $callback)
    {
        if ($this->connection === null) {
            $callback();
        }
    }

    /**
     * Escape the given field name according to PGSQL Standards
     * @param $field
     * @return string
     */
    public function escapeField($field)
    {
        return '"' . $field . '"';
    }

    /**
     * Start a transaction against the current connection
     * @throws Exception
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
     * Commit the transaction against the current connection
     * @throws Exception
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
     * Rollback the actual opened transaction
     * @throws Exception
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
     * Return the last inserted id
     * @return int
     */
    public function getInsertId()
    {
        $resultResource = pg_query($this->connection, 'SELECT lastval()');
        $row = pg_fetch_row($resultResource);
        return (int) $row[0];
    }

    /**
     * Give the number of affected rows
     * @return int
     */
    public function getAffectedRows()
    {
        if ($this->result === null) {
            return 0;
        }

        return pg_affected_rows($this->result);
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

    /**
     * @return bool true on success, false on failure
     * @throws NeverConnectedException when you have not been connected to your database before trying to pint it.
     */
    public function ping()
    {
        if ($this->connection === null) {
            throw new NeverConnectedException('Please connect to your database before trying to ping it.');
        }
        return pg_ping($this->connection);
    }
}
