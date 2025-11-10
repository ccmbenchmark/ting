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

use Generator;
use stdClass;

use function reset;

/**
 * @template T
 *
 * @template-extends Hydrator<T>
 */
class HydratorSingleObject extends Hydrator
{
    /**
     * @return Generator<int, T|stdClass|false>
     */
    public function getIterator(): Generator
    {
        foreach ($this->result as $key => $row) {
            $data = $this->hydrateColumns($this->result->getConnectionName(), $this->result->getDatabase(), $row);
            yield $key => reset($data);
        }
    }
}
