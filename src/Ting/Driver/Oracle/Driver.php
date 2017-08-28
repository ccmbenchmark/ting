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

namespace CCMBenchmark\Ting\Driver\Oracle;

use CCMBenchmark\Ting\Driver\DriverInterface;
use CCMBenchmark\Ting\Driver\Exception;
use CCMBenchmark\Ting\Driver\StatementInterface;
use CCMBenchmark\Ting\Logger\DriverLoggerInterface;
use CCMBenchmark\Ting\Repository\CollectionInterface;
use CCMBenchmark\Ting\Driver\QueryException;

class Driver implements DriverInterface
{
    /**
     * @var string|null
     */
    private $hostname;

    /**
     * @var string|null
     */
    private $username;

    /**
     * @var string|null
     */
    private $password;

    /**
     * @var int|null
     */
    private $port;

    /**
     * @var resource|null
     */
    private $connection;

    /**
     * @var string|null
     */
    private $name;

    /**
     * @var string|null
     */
    private $charset;

    /**
     * @var string|null
     */
    private $database;

    /**
     * @var DriverLoggerInterface
     */
    private $logger;

    /**
     * @var string hash of current object
     */
    private $objectHash = '';

    /**
     * @var array|null
     */
    private $preparedQueries;

    /**
     * @var bool
     */
    private $transactionOpened = false;

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
     * @param string $hostname
     * @param string $username
     * @param string $password
     * @param int $port
     *
     * @return $this
     */
    public function connect($hostname, $username, $password, $port)
    {
        $this->hostname = (string) $hostname;
        $this->username = (string) $username;
        $this->password = (string) $password;
        $this->port = (int) $port;

        return $this;
    }

    /**
     * Close the connection to the database
     *
     * @return $this
     */
    public function close()
    {
        if ($this->connection !== null) {
            oci_close($this->connection);
            $this->connection = null;
        }

        return $this;
    }

    /**
     * @param string $name
     *
     * @return $this
     */
    public function setName($name)
    {
        $this->name = (string) $name;

        return $this;
    }

    /**
     * @param string $charset
     *
     * @return void
     */
    public function setCharset($charset)
    {
        $charset = (string) $charset;
        if ($charset !== '' && $this->charset !== $charset) {
            $this->charset = $charset;
        }

        return;
    }

    /**
     * @param string $sql
     * @param array $params
     * @param CollectionInterface $collection
     *
     * @throws QueryException
     *
     * @return mixed
     */
    public function execute($sql, array $params = [], CollectionInterface $collection = null)
    {
        preg_match_all('/' . $this->parameterMatching . '/', $sql, $matches);
        foreach ($matches as $match) {
            if (array_key_exists($match, $params) === false) {
                throw new QueryException('Value has not been setted for param ' . $match);
            }
        }

        if ($this->logger !== null) {
            $this->logger->startQuery($sql, $params, $this->objectHash, $this->database);
        }

        $statement = oci_parse($this->connection, $sql);

        foreach ($params as $key => $value) {
            oci_bind_by_name($statement, ':' . $key, $value);
        }

        $result = oci_execute(
            $statement,
            $this->transactionOpened === true ? OCI_NO_AUTO_COMMIT : OCI_NO_AUTO_COMMIT
        );

        if ($this->logger !== null) {
            $this->logger->stopQuery();
        }

        if ($result === false) {
            $error = oci_error($this->connection);
            throw new QueryException($error['message'] . ' (Query: ' . $error['sqltext'] . ')', $error['code']);
        }

        if ($collection === null) {

        }
    }

    /**
     * @param string $sql
     *
     * @return StatementInterface
     *
     * @throws QueryException
     */
    public function prepare($sql)
    {

    }

    /**
     * Connection is made here because OCI8 can't change database on a same connection.
     *
     * @param string $database
     *
     * @throws Exception
     *
     * @return $this
     */
    public function setDatabase($database)
    {
        $database = (string) $database;
        if ($database === '' || $this->connection === null) {
            return $this;
        }

        $this->database = $database;
        $resource = oci_connect(
            $this->username,
            $this->password,
            $this->hostname . '/' . $this->database
        );

        if ($resource === false) {
            throw new Exception(
                sprintf(
                    'Connect error. Username: %s. Password: %s. Connection: %s',
                    $this->username, $this->password, $this->hostname . '/' . $this->database
                )
            );
        }

        $this->connection = $resource;
        return $this;
    }

    /**
     * @param callable $callback
     *
     * @return $this
     */
    public function ifIsError(callable $callback)
    {
        if ($this->connection === null) {
            return $this;
        }

        if (oci_error($this->connection) === false) {
            return $this;
        }

        $callback();
    }

    /**
     * @param callable $callback
     *
     * @return $this
     */
    public function ifIsNotConnected(callable $callback)
    {
        if ($this->connection === null) {
            $callback();
        }

        return $this;
    }

    /**
     * @param $field
     *
     * @return string
     */
    public function escapeField($field)
    {
        return '"' . $field . '"';
    }

    /**
     * Start a transaction.
     * A transaction is started when calling oci_execute with parameter OCI_NO_AUTO_COMMIT.
     * See https://secure.php.net/manual/en/function.oci-execute.php.
     *
     * @throws Exception
     */
    public function startTransaction()
    {
        if ($this->transactionOpened === true) {
            throw new Exception('Cannot start another transaction');
        }
        //@todo: handle it in execute method.
        $this->transactionOpened = true;
    }

    /**
     * Commit the current transaction.
     *
     * @throws Exception
     */
    public function commit()
    {
        if ($this->transactionOpened === false) {
            throw new Exception('Cannot commit no transaction');
        }

        oci_execute($this->connection);

        $this->transactionOpened = false;
    }

    /**
     * Rollback the current transaction.
     *
     * @throws Exception
     */
    public function rollback()
    {
        if ($this->transactionOpened === false) {
            throw new Exception('Cannot rollback no transaction');
        }

        oci_rollback($this->connection);

        $this->transactionOpened = false;
    }

    /**
     * @return int
     */
    public function getInsertId()
    {
        //@todo
    }

    /**
     * @return int
     */
    public function getAffectedRows()
    {
        //@todo
    }

    /**
     * @param DriverLoggerInterface|null $logger
     *
     * @return $this
     */
    public function setLogger(DriverLoggerInterface $logger = null)
    {
        $this->logger = $logger;
        $this->objectHash = spl_object_hash($logger);

        return $this;
    }

    /**
     * @param array $connectionConfig
     * @param string $database
     *
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
     * @param $statement
     *
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
