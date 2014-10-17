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

class CachedCollection implements CollectionInterface, \Iterator
{

    protected $hydrator = null;
    protected $rows     = [];
    protected $internalRows = [];
    protected $length   = 0;
    protected $current  = 0;

    public function __construct(HydratorInterface $hydrator = null)
    {
        $this->hydrator = $hydrator;
    }

    public function set(ResultInterface $result)
    {
        foreach ($result as $row) {
            if ($this->hydrator === null) {
                $data = [];
                foreach ($row as $column) {
                    $data[$column['name']] = $column['value'];
                }
                $this->add($data);
            } else {
                $this->hydrator->hydrate($row, $this);
            }
            $this->internalRows[] = $row;
        }
    }

    public function add($data, $key = null)
    {
        if ($key === null) {
            $this->rows[] = $data;
        } else {
            $this->rows[$key] = $data;
        }
    }

    public function toArray()
    {
        return $this->internalRows;
    }

    public function fromArray(array $rows)
    {
        $this->internalRows = $rows;
        foreach ($this->internalRows as $row) {
            if ($this->hydrator === null) {
                $data = [];
                foreach ($row as $column) {
                    $data[$column['name']] = $column['value'];
                }
                $this->add($data);
            } else {
                $this->hydrator->hydrate($row, $this);
            }
        }
    }

    /**
    * Iterator
    */
    public function rewind()
    {
        reset($this->rows);
        return $this;
    }

    public function current()
    {
        return current($this->rows);
    }

    public function key()
    {
        return key($this->rows);
    }

    public function next()
    {
        return next($this->rows);
    }

    public function valid()
    {
        if (current($this->rows) === false) {
            return false;
        }

        return true;
    }
}
