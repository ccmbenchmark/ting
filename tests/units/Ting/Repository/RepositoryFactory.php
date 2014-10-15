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

namespace tests\units\CCMBenchmark\Ting\Repository;

use mageekguy\atoum;

class RepositoryFactory extends atoum
{
    public function testGet()
    {
        $services = new \CCMBenchmark\Ting\Services();

        $this
            ->if($repositoryFactory = new \CCMBenchmark\Ting\Repository\RepositoryFactory(
                $services->get('ConnectionPool'),
                $services->get('MetadataRepository'),
                $services->get('MetadataFactory'),
                $services->get('CollectionFactory'),
                $services->get('UnitOfWork')
            ))
            ->and($repository = $repositoryFactory->get('\mock\tests\fixtures\model\BouhRepository'))
            ->object($repository)
                ->isInstanceOf('\mock\tests\fixtures\model\BouhRepository');
    }
}
