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

namespace tests\fixtures\FakeDriver;

use CCMBenchmark\Ting\Driver\ResultInterface;

class MysqliResult implements ResultInterface
{

    protected $offset = 0;
    protected $data   = null;

    public function __construct(array $data = [])
    {
        $this->data = $data;

    }

    public function setResult($iterator)
    {
        $this->data = iterator_to_array($iterator);
        return $this;
    }

    // @codingStandardsIgnoreStart
    public function fetch_fields()
    {

    }

    public function fetch_assoc()
    {
        return $this->data;
    }

    public function data_seek()
    {

    }

    public function fetch_array($type)
    {
        $data = $this->current();
        $this->next();
        return $data;
    }
    // @codingStandardsIgnoreEnd

    #[\ReturnTypeWillChange]
    public function rewind()
    {
        $this->offset = 0;
    }

    #[\ReturnTypeWillChange]
    public function current()
    {
        if (isset($this->data[$this->offset]) === false) {
            return null;
        }

        return $this->data[$this->offset];
    }

    #[\ReturnTypeWillChange]
    public function key()
    {
        return $this->offset;
    }

    #[\ReturnTypeWillChange]
    public function next()
    {
        ++$this->offset;
    }

    #[\ReturnTypeWillChange]
    public function valid()
    {
        return isset($this->data[$this->offset]);
    }

    public function getNumRows()
    {
        return count($this->data);
    }

    public function setConnectionName($connectionName)
    {

    }

    public function setDatabase($database)
    {

    }

    public function getConnectionName()
    {

    }

    public function getDatabase()
    {

    }
}
