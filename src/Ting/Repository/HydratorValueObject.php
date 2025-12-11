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
use CCMBenchmark\Ting\Exception;
use CCMBenchmark\Ting\MetadataRepository;
use CCMBenchmark\Ting\UnitOfWork;

/**
 * @template T
 *
 * @template-implements HydratorInterface<T>
 */
class HydratorValueObject implements HydratorInterface
{
    /**
     * @var class-string<T>
     */
    protected $objectToHydrate;
    /**
     * @var ResultInterface<T>
     */
    protected $result = null;


    /**
     * @param class-string<T> $objectToHydrate
     */
    public function __construct(string $objectToHydrate)
    {
        $this->objectToHydrate = $objectToHydrate;
    }

    /**
     * @return \Generator<int, T>
     */
    public function getIterator(): \Generator
    {
        $this->result->setObjectToFetch($this->objectToHydrate);

        foreach ($this->result as $key => $row) {
            yield $key => $row;
        }
    }

    /**
     * @return int
     */
    public function count(): int
    {
        if ($this->result === null) {
            return 0;
        }

        return $this->result->getNumRows();
    }

    public function setMetadataRepository(MetadataRepository $metadataRepository): void
    {
        // Useless for this hydrator
    }

    public function setUnitOfWork(UnitOfWork $unitOfWork): void
    {
        // Useless for this hydrator
    }

    public function setResult(ResultInterface $result): static
    {
        $this->result = $result;
        return $this;
    }
}
