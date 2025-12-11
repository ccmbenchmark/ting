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

    public function setResult($iterator): static
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

    public function rewind(): void
    {
        $this->offset = 0;
    }

    public function current(): mixed
    {
        if (isset($this->data[$this->offset]) === false) {
            return null;
        }

        return $this->data[$this->offset];
    }

    public function key(): mixed
    {
        return $this->offset;
    }

    public function next(): void
    {
        ++$this->offset;
    }

    public function valid(): bool
    {
        return isset($this->data[$this->offset]);
    }

    public function getNumRows(): int
    {
        return count($this->data);
    }

    public function setConnectionName($connectionName): static
    {
        return $this;
    }

    public function setDatabase($database): static
    {
        return $this;
    }

    public function getConnectionName(): ?string
    {

    }

    public function getDatabase(): ?string
    {
    }

    public function setObjectToFetch(string $objectToFetch): static
    {
    }

    public function fetch_object($class_name = null)
    {
    }
}
