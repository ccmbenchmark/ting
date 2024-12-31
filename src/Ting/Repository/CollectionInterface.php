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

use CCMBenchmark\Ting\Driver\ResultInterface;

/**
 * @template T
 *
 * @template-extends \IteratorAggregate<int, T>
 */
interface CollectionInterface extends \IteratorAggregate, \Countable
{
    /**
     * Fill collection from iterator
     * @param ResultInterface<T> $result
     * @return void
     */
    public function set(ResultInterface $result);

    /**
     * @return T
     */
    public function first();

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
    public function toCache();

    /**
     * @param array $result
     * @return void
     */
    public function fromCache(array $result);
}
