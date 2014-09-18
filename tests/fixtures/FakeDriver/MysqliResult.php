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

class MysqliResult implements \Iterator
{

    protected $offset = 0;
    protected $data   = null;

    public function __construct($data)
    {
        $this->data = $data;
    }

    // @codingStandardsIgnoreStart
    public function fetch_fields()
    {

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

    public function rewind()
    {
        $this->offset = 0;
    }

    public function current()
    {
        return $this->data[$this->offset];
    }

    public function key()
    {
        return $this->offset;
    }

    public function next()
    {
        $this->offset++;
    }

    public function valid()
    {
        return isset($this->data[$this->offset]);
    }
}
