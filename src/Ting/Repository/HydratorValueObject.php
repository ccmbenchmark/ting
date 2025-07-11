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
use CCMBenchmark\Ting\MetadataRepository;
use CCMBenchmark\Ting\UnitOfWork;
use CCMBenchmark\Ting\Util\PropertyAccessor;
use DateTime;
use Symfony\Component\PropertyAccess\PropertyAccess;

use function reset;

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
     * @var PropertyAccessor
     */
    protected $propertyAccessor;
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
        $this->propertyAccessor = new PropertyAccessor();
    }

    /**
     * @param MetadataRepository $metadataRepository
     * @return void
     */
    public function setMetadataRepository(MetadataRepository $metadataRepository)
    {
        // Useless for this hydrator
    }

    /**
     * @param UnitOfWork $unitOfWork
     * @return void
     */
    public function setUnitOfWork(UnitOfWork $unitOfWork)
    {
        // Useless for this hydrator
    }

    /**
     * @param ResultInterface<T> $result
     * @return $this
     */
    public function setResult(ResultInterface $result)
    {
        $this->result = $result;
        return $this;
    }

    /**
     * @return \Generator<int, T>
     */
    #[\ReturnTypeWillChange]
    public function getIterator()
    {
        foreach ($this->result as $key => $row) {
            yield $key => $this->hydrateColumns($row);
        }
    }

    /**
     * @return int
     */
    #[\ReturnTypeWillChange]
    public function count()
    {
        if ($this->result === null) {
            return 0;
        }

        return $this->result->getNumRows();
    }

    private function hydrateColumns(array $rows)
    {
        $object = new $this->objectToHydrate();
        foreach ($rows as $column) {
            $reflectionData = $this->propertyAccessor->getReflectionData($object, $column['name']);
            if (in_array($reflectionData['type'], ['DateTime', 'DateTimeImmutable'])) {
                $column['value'] = new $reflectionData['type']($column['value']);
            }
            $this->propertyAccessor->setValue($object, $column['name'], $column['value']);
        }

        return $object;
    }

    private function getSerializerFromType(string $type)
    {

    }
}
