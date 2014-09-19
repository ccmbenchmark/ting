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

namespace tests\units\CCMBenchmark\Ting;

use \mageekguy\atoum;

class MetadataRepository extends atoum
{
    public function testFindMetadataForEntityShouldCallCallbackFound()
    {
        $services = new \CCMBenchmark\Ting\Services();
        $metadata = new \CCMBenchmark\Ting\Repository\Metadata($services->get('QueryFactory'));
        $metadata->setClass('tests\fixtures\model\BouhRepository');

        $metadataRepository = new \CCMBenchmark\Ting\MetadataRepository($services->get('MetadataFactory'));
        $metadataRepository->addMetadata('tests\fixtures\model\BouhRepository', $metadata);

        $entity = new \tests\fixtures\model\Bouh();

        $this
            ->if($metadataRepository->findMetadataForEntity(
                $entity,
                function ($metadata) use (&$outerCallbackFound) {
                    $outerCallbackFound = true;
                },
                function () use (&$outerCallbackNotFound) {
                    $outerCallbackNotFound = true;
                }
            ))
            ->boolean($outerCallbackFound)
                ->isTrue()
            ->variable($outerCallbackNotFound)
                ->isNull();
    }

    public function testFindMetadataForEntityShouldCallCallbackNotFound()
    {
        $services = new \CCMBenchmark\Ting\Services();
        $metadata = new \CCMBenchmark\Ting\Repository\Metadata($services->get('QueryFactory'));
        $metadata->setClass('tests\fixtures\model\BouhRepository');

        $metadataRepository = new \CCMBenchmark\Ting\MetadataRepository($services->get('MetadataFactory'));
        $metadataRepository->addMetadata('tests\fixtures\model\BouhRepository', $metadata);

        $entity = new \mock\tests\fixtures\model\Bouh2();

        $this
            ->if($metadataRepository->findMetadataForEntity(
                $entity,
                function ($metadata) use (&$outerCallbackFound) {
                    $outerCallbackFound = true;
                },
                function () use (&$outerCallbackNotFound) {
                    $outerCallbackNotFound = true;
                }
            ))
            ->boolean($outerCallbackNotFound)
                ->isTrue()
            ->variable($outerCallbackFound)
                ->isNull();
    }

    public function testFindMetadataForTableShouldCallCallbackFound()
    {
        $services = new \CCMBenchmark\Ting\Services();
        $metadata = new \CCMBenchmark\Ting\Repository\Metadata($services->get('QueryFactory'));
        $metadata->setTable('T_BOUH_BOO');

        $metadataRepository = new \CCMBenchmark\Ting\MetadataRepository($services->get('MetadataFactory'));
        $metadataRepository->addMetadata('tests\fixtures\model\BouhRepository', $metadata);

        $this
            ->if($metadataRepository->findMetadataForTable(
                'T_BOUH_BOO',
                function ($metadata) use (&$outerCallbackFound) {
                    $outerCallbackFound = true;
                },
                function () use (&$outerCallbackNotFound) {
                    $outerCallbackNotFound = true;
                }
            ))
            ->boolean($outerCallbackFound)
                ->isTrue()
            ->variable($outerCallbackNotFound)
                ->isNull();
    }

    public function testFindMetadataForTableShouldCallCallbackNotFound()
    {
        $services = new \CCMBenchmark\Ting\Services();
        $metadata = new \CCMBenchmark\Ting\Repository\Metadata($services->get('QueryFactory'));
        $metadata->setTable('T_BOUH_BOO');

        $metadataRepository = new \CCMBenchmark\Ting\MetadataRepository($services->get('MetadataFactory'));
        $metadataRepository->addMetadata('tests\fixtures\model\BouhRepository', $metadata);

        $this
            ->if($metadataRepository->findMetadataForTable(
                'T_BOUH2_BOO',
                function ($metadata) use (&$outerCallbackFound) {
                    $outerCallbackFound = true;
                },
                function () use (&$outerCallbackNotFound) {
                    $outerCallbackNotFound = true;
                }
            ))
            ->boolean($outerCallbackNotFound)
                ->isTrue()
            ->variable($outerCallbackFound)
                ->isNull();
    }

    public function testBatchLoadMetadataShouldLoad1Repository()
    {
        $services = new \CCMBenchmark\Ting\Services();
        $this
            ->if(
                $metadataRepository = new \CCMBenchmark\Ting\MetadataRepository(
                    $services->get('MetadataFactory')
                )
            )
            ->variable($return = $metadataRepository->batchLoadMetadata(
                'tests\fixtures\model',
                __DIR__ . '/../../fixtures/model/*Repository.php'
            ))
                ->isIdenticalTo(1);
    }

    public function testBatchLoadMetadataWithInvalidPathShouldReturn0()
    {
        $services = new \CCMBenchmark\Ting\Services();
        $this
            ->if(
                $metadataRepository = new \CCMBenchmark\Ting\MetadataRepository(
                    $services->get('MetadataFactory')
                )
            )
            ->variable($return = $metadataRepository->batchLoadMetadata(
                'tests\fixtures\model',
                '/not/valid/path/*Repository.php'
            ))
                ->isIdenticalTo(0);
    }
}
