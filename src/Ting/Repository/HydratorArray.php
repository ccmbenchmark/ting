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
use CCMBenchmark\Ting\Driver\ResultInterface;
use CCMBenchmark\Ting\MetadataRepository;
use CCMBenchmark\Ting\UnitOfWork;

/**
 * @template T
 *
 * @template-implements HydratorInterface<T>
 */
class HydratorArray implements HydratorInterface
{
    protected $metadataRepository = null;
    protected $unitOfWork         = null;

    /**
     * @var ResultInterface
     */
    protected $result = null;

    /**
     * @param MetadataRepository $metadataRepository
     * @return void
     */
    public function setMetadataRepository(MetadataRepository $metadataRepository): void
    {
        // Useless for this hydrator
    }

    /**
     * @param UnitOfWork $unitOfWork
     * @return void
     */
    public function setUnitOfWork(UnitOfWork $unitOfWork): void
    {
        // Useless for this hydrator
    }

    public function setResult(ResultInterface $result): static
    {
        $this->result = $result;
        return $this;
    }

    /**
     * @return Generator<int, T>
     */
    public function getIterator(): Generator
    {
        foreach ($this->result as $key => $row) {
            $data = [];
            foreach ($row as $column) {
                $data[$column['name']] = $column['value'];
            }

            yield $key => $data;
        }
    }

    /**
     * @return int<0, max>
     */
    public function count(): int
    {
        if ($this->result === null) {
            return 0;
        }

        return $this->result->getNumRows();
    }
}
