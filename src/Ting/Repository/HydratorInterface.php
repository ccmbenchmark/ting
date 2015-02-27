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

interface HydratorInterface
{

    /**
     * @param MetadataRepository $metadataRepository
     * @return void
     */
    public function setMetadataRepository(MetadataRepository $metadataRepository);

    /**
     * @param UnitOfWork $unitOfWork
     * @return void
     */
    public function setUnitOfWork(UnitOfWork $unitOfWork);


    /**
     * Hydrate One object from it's values
     * @param string              $connectionName
     * @param string              $database
     * @param array               $columns
     * @param CollectionInterface $collection
     *
     * @return mixed
     */
    public function hydrate($connectionName, $database, array $columns, CollectionInterface $collection);
}
