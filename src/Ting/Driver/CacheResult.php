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

use Iterator;

class CacheResult implements ResultInterface
{
    /**
     * @var string|null
     */
    protected $connectionName = null;

    /**
     * @var string|null
     */
    protected ?string $database = null;

    /**
     * @var Iterator|null
     */
    protected $result = null;


    /**
     * @param string $connectionName
     * @return $this
     */
    public function setConnectionName(string $connectionName): static
    {
        $this->connectionName = (string) $connectionName;
        return $this;
    }

    /**
     * @param string $database
     * @return $this
     */
    public function setDatabase(string $database): static
    {
        $this->database = $database;
        return $this;
    }

    /**
     * @param Iterator $result
     * @return $this
     */
    public function setResult($result): static
    {
        $this->result = $result;
        return $this;
    }

    public function getConnectionName(): ?string
    {
        return $this->connectionName;
    }

    public function getDatabase(): ?string
    {
        return $this->database;
    }

    /**
     * Iterator
     */
    public function rewind(): void
    {
        if ($this->result !== null) {
            $this->result->rewind();
        }
    }

    /**
     * Return current row
     */
    public function current(): mixed
    {
        if ($this->result === null) {
            return null;
        }

        return $this->result->current();
    }

    /**
     * Return the key of the actual row
     */
    public function key(): mixed
    {
        if ($this->result === null) {
            return null;
        }

        return $this->result->key();
    }

    /**
     * Move to the next row in result set
     */
    public function next(): void
    {
        if ($this->result !== null) {
            $this->result->next();
        }
    }

    /**
     * Is the actual row valid
     */
    public function valid(): bool
    {
        if ($this->result === null) {
            return false;
        }

        return $this->result->valid();
    }

    public function getNumRows(): int
    {
        return $this->result !== null ? iterator_count($this->result) : 0;
    }
}
