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

use CCMBenchmark\Ting\ConnectionPool;
use CCMBenchmark\Ting\Repository\Collection;
use CCMBenchmark\Ting\Repository\Hydrator;

class RepositoryFactory
{
    public function __construct(
        ConnectionPool $connectionPool,
        MetadataRepository $metadataRepository,
        MetadataFactoryInterface $metadataFactory,
        Collection $collection,
        Hydrator $hydrator
    ) {
        $this->connectionPool     = $connectionPool;
        $this->metadataRepository = $metadataRepository;
        $this->metadataFactory    = $metadataFactory;
        $this->collection         = $collection;
        $this->hydrator           = $hydrator;
    }

    public function get($repositoryName)
    {
        return new $repositoryName(
            $this->connectionPool,
            $this->metadataRepository,
            $this->metadataFactory,
            $this->collection,
            $this->hydrator
        );
    }
}
