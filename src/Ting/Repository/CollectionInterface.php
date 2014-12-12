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

namespace CCMBenchmark\Ting\Repository;

interface CollectionInterface
{
    public function set(\Iterator $result);

    /**
     * @return mixed
     */
    public function first();

    /**
     * Iterator
     */
    public function rewind();

    public function current();

    public function key();

    public function next();

    public function valid();

    public function count();

    public function add($data, $key = null);

    /**
     * @param bool $value
     * @return void
     */
    public function setFromCache($value);

    /**
     * @return bool
     */
    public function isFromCache();

    /**
     * @return array
     */
    public function toArray();

    /**
     * @param array $rows
     * @return void
     */
    public function fromArray(array $rows);
}
