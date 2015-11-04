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

class Collection implements CollectionInterface, \Iterator, \Countable
{

    /**
     * Use when Hydrator is not an aggregator
     *
     * @var \Iterator
     */
    protected $iterator = null;

    /**
     * Used when Hydrator is an aggregator
     *
     * @var array
     */
    protected $rows = [];


    /**
     * @var HydratorInterface|null
     */
    protected $hydrator = null;

    /**
     * @var bool
     */
    protected $fromCache = false;

    /**
     * @var bool
     */
    protected $isCacheable = false;

    /**
     * @var array
     */
    protected $internalRows = [];

    /**
     * @param HydratorInterface $hydrator
     */
    public function __construct(HydratorInterface $hydrator = null)
    {
        $this->hydrator = $hydrator;
    }

    /**
     * Fill collection from iterator
     * @param \Iterator $result
     * @return void
     */
    public function set(\Iterator $result)
    {
        if ($this->shouldUseArray() === false) {
            $this->iterator = $result;
            return;
        }

        foreach ($result as $row) {
            if ($this->hydrator === null) {
                $data = [];
                foreach ($row as $column) {
                    $data[$column['name']] = $column['value'];
                }
                $this->add($data);
            } else {
                $this->hydrate($row);
            }
        }
    }

    /**
     * @return bool
     */
    private function shouldUseArray()
    {

        if ($this->hydrator !== null) {
            return $this->hydrator->isAggregator();
        }

        if ($this->iterator === null) {
            return true;
        }

        return false;
    }

    /**
     * @return mixed
     */
    private function hydrate($data)
    {
        if ($this->hydrator === null) {
            return $data;
        }

        if ($data === null) {
            return false;
        }

        return $this->hydrator->hydrate($data, $this);
    }

    /**
     * Add a row in the collection
     * @param mixed $data
     * @param string|null $key
     * @return void
     */
    public function add($data, $key = null)
    {
        if ($key === null) {
            $this->rows[] = $data;
        } else {
            $this->rows[$key] = $data;
        }
    }

    /**
     * @param bool $value
     * @return void
     */
    public function setFromCache($value)
    {
        $this->fromCache   = (bool) $value;
        $this->isCacheable = true;
    }

    /**
     * @return bool
     */
    public function isFromCache()
    {
        return $this->fromCache;
    }

    /**
     * @return array
     */
    public function toArray()
    {
        if ($this->shouldUseArray() === false) {
            return iterator_to_array($this->iterator);
        }

        return $this->rows;
    }

    /**
     * @param array $rows
     * @return void
     */
    public function fromArray(array $rows)
    {
        $this->set(new \ArrayIterator($rows));
    }

    /**
     * @return mixed
     */
    public function first()
    {
        $result = $this->rewind()->current();

        if ($this->shouldUseArray() === false) {
            $result = $this->hydrate($result, $this);
        }

        if ($result === false) {
            return null;
        }

        return $result;
    }

    /**
     * Iterator
     */

    /**
     * @return $this
     */
    public function rewind()
    {
        if ($this->shouldUseArray() === false) {
            $this->iterator->rewind();
        } else {
            reset($this->rows);
        }
        return $this;
    }

    /**
     * @return mixed
     */
    public function current()
    {
        if ($this->shouldUseArray() === false) {
            $result = $this->hydrate($this->iterator->current(), $this);
        } else {
            $result = current($this->rows);
        }

        return $result;
    }

    /**
     * @return mixed
     */
    public function key()
    {
        if ($this->shouldUseArray() === false) {
            $key = $this->iterator->key();
        } else {
            $key = key($this->rows);
        }

        return $key;
    }

    /**
     * @return mixed
     */
    public function next()
    {
        if ($this->shouldUseArray() === false) {
            $this->iterator->next();
            $result = $this->current();
        } else {
            $result = next($this->rows);
        }

        return $result;
    }

    /**
     * @return bool
     */
    public function valid()
    {
        if ($this->current() === false) {
            return false;
        }

        return true;
    }

    /**
     * @return int
     */
    public function count()
    {
        if ($this->shouldUseArray() === true) {
            return count($this->rows);
        }

        return $this->iterator->getNumRows();
    }
}
