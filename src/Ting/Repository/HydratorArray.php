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

use CCMBenchmark\Ting\MetadataRepository;
use CCMBenchmark\Ting\UnitOfWork;

class HydratorArray implements HydratorInterface
{

    protected $metadataRepository = null;
    protected $unitOfWork         = null;

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
     * Hydrate one object from values and add to Collection
     * @param string              $connectionName
     * @param string              $database
     * @param array               $columns
     * @param CollectionInterface $collection
     * @return array
     */
    public function hydrate($connectionName, $database, array $columns, CollectionInterface $collection)
    {
        $result = [];

        foreach ($columns as $column) {
            $result[$column['name']] = $column['value'];
        }

        $collection->add($result);
        return $result;
    }
}
