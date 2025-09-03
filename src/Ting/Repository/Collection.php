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

use JsonSerializable;
use ArrayIterator;
use Generator;
use CCMBenchmark\Ting\Driver\CacheResult;
use CCMBenchmark\Ting\Driver\ResultInterface;

/**
 * @template T
 *
 * @template-implements CollectionInterface<T>
 */
class Collection implements CollectionInterface, JsonSerializable
{
    /**
     * @var ResultInterface<T>
     */
    protected ?ResultInterface $result = null;

    protected ?HydratorInterface $hydrator;

    /**
     * @var bool
     */
    protected $fromCache = false;

    /**
     * @param HydratorInterface<T>|null $hydrator
     */
    public function __construct(?HydratorInterface $hydrator = null)
    {
        $this->hydrator = $hydrator ?? new HydratorArray();
    }

    public function set(ResultInterface $result): void
    {
        $this->result = $result;
        $this->hydrator->setResult($result);
    }

    /**
     * @param bool $value
     * @return void
     */
    public function setFromCache($value): void
    {
        $this->fromCache = (bool) $value;
    }

    /**
     * @return bool
     */
    public function isFromCache(): bool
    {
        return $this->fromCache;
    }

    /**
     * @return array
     */
    public function toCache(): array
    {
        $connectionName = null;
        $database = null;
        $data = [];

        if ($this->result !== null) {
            $connectionName = $this->result->getConnectionName();
            $database = $this->result->getDatabase();
            $data = iterator_to_array($this->result);
        }

        return [
            'connection' => $connectionName,
            'database' => $database,
            'data' => $data
        ];
    }

    /**
     * @param array $result
     * @return void
     */
    public function fromCache(array $result): void
    {
        $this->setFromCache(true);
        $cacheResult = new CacheResult();
        $cacheResult->setConnectionName($result['connection']);
        $cacheResult->setDatabase($result['database']);
        $cacheResult->setResult(new ArrayIterator($result['data']));
        $this->set($cacheResult);
    }

    public function first()
    {
        if ($this->result === null) {
            return null;
        }

        $iterator = $this->getIterator();

        /**
         * Some iterator need to be rewind to use current
         */
        $iterator->rewind();

        return $iterator->current();
    }


    /**
     * @return Generator<int, T>
     */
    public function getIterator(): \Generator
    {
        return $this->hydrator->getIterator();
    }

    public function count(): int
    {
        return $this->hydrator->count();
    }

    public function jsonSerialize(): array
    {
        return iterator_to_array($this->hydrator->getIterator());
    }
}
