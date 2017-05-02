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

use CCMBenchmark\Ting\Serializer\RuntimeException;

class HydratorAggregator extends Hydrator
{
    private $callableForId;
    private $callableForData;
    private $key;

    public function callableIdIs(callable $callableForId)
    {
        $this->callableForId = $callableForId;
    }

    public function callableDataIs(callable $callableForData)
    {
        $this->callableForData = $callableForData;
    }

    public function aggregateToKey($key)
    {
        $this->key = (string) $key;
    }

    /**
     * @return \Generator
     */
    public function getIterator()
    {
        $knownIdentifiers = [];
        $callableForId = $this->callableForId;
        $callableForData = $this->callableForData;
        $previousId = null;
        $currentId = null;
        $aggregate = [];
        $key = null;
        $result = null;

        foreach ($this->result as $key => $columns) {
            $result = $this->hydrateColumns(
                $this->result->getConnectionName(),
                $this->result->getDatabase(),
                $columns
            );

            $currentId = $callableForId($result);

            if (in_array($currentId, $knownIdentifiers, true) === true) {
                // throw new RuntimeException("Identifier $currentId already generated, please sort your result by identifier");
                // Pas le droit de lever une exception, c'est pas dans l'interface, je fais comment du coup ? :D
            }

            if ($previousId === null) {
                $previousId = $currentId;
            }

            if ($previousId === $currentId) {
                $aggregate[] = $callableForData($result);
            } else {
                $result[$this->key] = $aggregate;
                $aggregate = [];
                $knownIdentifiers[] = $previousId;
                $previousId = $currentId;

                yield $key => $result;
            }
        }

        if ($previousId !== $currentId) {
            yield $key => $result;
        }
    }
}
