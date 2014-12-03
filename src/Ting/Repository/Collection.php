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

class Collection implements CollectionInterface, \Iterator
{

    /**
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
        if ($this->isCacheable === true) {
            $this->internalRows = iterator_to_array($result);
        }

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
        }
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
        return $this->internalRows;
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
     * @return @mixed
     */
    public function first()
    {
        $result = $this->rewind()->current();

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
        reset($this->rows);
        return $this;
    }

    /**
     * @return mixed
     */
    public function current()
    {
        return current($this->rows);
    }

    /**
     * @return mixed
     */
    public function key()
    {
        return key($this->rows);
    }

    /**
     * @return mixed
     */
    public function next()
    {
        return next($this->rows);
    }

    /**
     * @return bool
     */
    public function valid()
    {
        if (current($this->rows) === false) {
            return false;
        }

        return true;
    }

    /**
     * @return int
     */
    public function count()
    {
        return count($this->rows);
    }
}
