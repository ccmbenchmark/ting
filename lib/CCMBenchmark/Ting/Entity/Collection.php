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

namespace CCMBenchmark\Ting\Entity;

use CCMBenchmark\Ting\Driver\ResultInterface;

class Collection implements \Iterator
{

    protected $result   = null;
    protected $hydrator = null;

    public function set(ResultInterface $result)
    {
        $this->result = $result;
    }

    public function hydrate($data)
    {
        if ($data === null) {
            return null;
        }

        if ($this->hydrator !== null) {
            return $this->hydrator->hydrate($data);
        }

        return $data;
    }

    public function hydrator(Hydrator $hydrator)
    {
        $this->hydrator = $hydrator;
        return $this;
    }

    /**
     * Iterator
     */
    public function rewind()
    {
        $this->result->rewind();
        return $this;
    }

    public function current()
    {
        return $this->hydrate($this->result->current());
    }

    public function key()
    {
        return $this->result->key();
    }

    public function next()
    {
        $this->result->next();
        return $this;
    }

    public function valid()
    {
        return $this->result->valid();
    }
}
