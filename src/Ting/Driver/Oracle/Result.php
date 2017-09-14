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

use CCMBenchmark\Ting\Driver\ResultInterface;

class Result implements ResultInterface
{
    /**
     * @var string|null
     */
    private $connectionName;

    /**
     * @var array|null
     */
    private $result;

    /**
     * @var string|null
     */
    private $database;

    /**
     * @param string $connectionName
     * @return $this
     */
    public function setConnectionName($connectionName)
    {
        $this->connectionName = (string) $connectionName;
        return $this;
    }

    /**
     * @param string $database
     * @return $this
     */
    public function setDatabase($database)
    {
        $this->database = (string) $database;
        return $this;
    }

    /**
     * @param array $result
     * @return $this
     */
    public function setResult($result)
    {
        $this->result = $result;
        return $this;
    }

    /**
     * @return string|null
     */
    public function getConnectionName()
    {
        return $this->connectionName;
    }

    /**
     * @return string|null
     */
    public function getDatabase()
    {
        return $this->database;
    }

    /**
     * @return int
     */
    public function getNumRows()
    {
        if (is_array($this->result) === false) {
            return 0;
        }

        return count($this->result);
    }
}
