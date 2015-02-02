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

class CollectionFactory implements CollectionFactoryInterface
{

    /**
     * @var MetadataRepository|null
     */
    protected $metadataRepository = null;

    /**
     * @var UnitOfWork|null
     */
    protected $unitOfWork = null;

    /**
     * @var HydratorInterface|null
     */
    protected $hydrator = null;

    /**
     * @param MetadataRepository $metadataRepository
     * @param UnitOfWork $unitOfWork
     * @param HydratorInterface $hydrator
     */
    public function __construct(
        MetadataRepository $metadataRepository,
        UnitOfWork $unitOfWork,
        HydratorInterface $hydrator
    ) {
        $this->metadataRepository = $metadataRepository;
        $this->unitOfWork = $unitOfWork;
        $this->hydrator = $hydrator;
        $this->hydrator->setMetadataRepository($this->metadataRepository);
        $this->hydrator->setUnitOfWork($this->unitOfWork);
    }

    /**
     * @param HydratorInterface $hydrator
     * @return Collection
     */
    public function get(HydratorInterface $hydrator = null)
    {
        if ($hydrator === null) {
            $hydrator = $this->hydrator;
        } else {
            $hydrator->setMetadataRepository($this->metadataRepository);
            $hydrator->setUnitOfWork($this->unitOfWork);
        }

        return new Collection($hydrator);
    }
}
