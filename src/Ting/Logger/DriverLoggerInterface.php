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

namespace CCMBenchmark\Ting\Logger;

interface DriverLoggerInterface
{
    /**
     * Add an opened connection to the list
     *
     * @param string $name connection name
     * @param string $connection spl_object_hash of the connection
     * @param array $connectionConfig Connection parameters
     * @return void
     */
    public function addConnection($name, $connection, array $connectionConfig);

    /**
     * Logs a SQL Query
     *
     * @param string $sql
     * @param array $params
     * @param string $connection spl_object_hash of the connection
     * @param string $database name of the database
     * @return void
     */
    public function startQuery($sql, $params, $connection, $database);

    /**
     * Log the preparation of a statement
     *
     * @param string $sql the query
     * @param string $connection spl_object_hash of the connection
     * @param string $database name of the database
     * @return void
     */
    public function startPrepare($sql, $connection, $database);

    /**
     * Log the parameters applied to a statement when executed
     *
     * @param string $statement statement name
     * @param array $params
     * @return void
     */
    public function startStatementExecute($statement, $params);


    /**
     * Log the end of a query (for timing purposes mainly)
     *
     * @return void
     */
    public function stopQuery();

    /**
     * Log the end of the preparation (for timing purposes)
     *
     * @param string $statement statement name
     * @return void
     */
    public function stopPrepare($statement);

    /**
     * Log the end of execution of a prepared statement
     *
     * @param string $statement statement name
     * @return void
     */
    public function stopStatementExecute($statement);
}
