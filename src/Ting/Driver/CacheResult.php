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

namespace CCMBenchmark\Ting\Driver;

use CCMBenchmark\Ting\Exception;

class CacheResult implements ResultInterface
{

    protected $connectionName;
    protected $database;
    protected $result;

    /**
     * @param string    $connectionName
     * @param string    $database
     * @param \Iterator $result
     */
    public function __construct($connectionName, $database, $result)
    {
        $this->connectionName = $connectionName;
        $this->database       = $database;
        $this->result         = $result;
    }

    public function getConnectionName()
    {
        return $this->connectionName;
    }

    public function getDatabase()
    {
        return $this->database;
    }

    public function dataSeek($offset)
    {
        throw new Exception('CacheResult::dataSeek can\'t be called');
    }

    public function format($data)
    {
        throw new Exception('Cacheresult::format can\'t be called');
    }

    /**
     * Iterator
     */
    public function rewind()
    {
        $this->result->rewind();
    }

    /**
     * Return current row
     * @return mixed|null
     */
    public function current()
    {
        return $this->result->current();
    }

    /**
     * Return the key of the actual row
     * @return int|mixed
     */
    public function key()
    {
        return $this->result->key();
    }

    /**
     * Move to the next row in result set
     */
    public function next()
    {
        $this->result->next();
    }

    /**
     * Is the actual row valid
     * @return bool
     */
    public function valid()
    {
        return $this->result->valid();
    }
}
