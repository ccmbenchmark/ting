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

use IteratorAggregate;
use Countable;
use Iterator;
use CCMBenchmark\Ting\Driver\ResultInterface;

/**
 * @template T
 *
 * @template-extends IteratorAggregate<int, T>
 */
interface CollectionInterface extends IteratorAggregate
{
    /**
     * Fill collection from iterator
     * @param ResultInterface<T> $result
     * @return void
     */
    public function set(ResultInterface $result): void;

    /**
     * @return T|null
     */
    public function first();

    /**
     * @param bool $value
     * @return void
     */
    public function setFromCache($value): void;

    public function isFromCache(): bool;

    /**
     * @return array{connection: ?string, database: ?string, data: array}
     */
    public function toCache(): array;

    public function fromCache(array $result): void;

    /**
     * @return \Generator<int, T>
     */
    public function getIterator(): \Generator;

    /**
     * @return int<0, max>|string
     */
    public function count(): int|string;
}
