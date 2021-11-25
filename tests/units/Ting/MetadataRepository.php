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

use mageekguy\atoum;

class MetadataRepository extends atoum
{
    public function testFindMetadataForEntityShouldCallCallbackFound()
    {
        $services = new \CCMBenchmark\Ting\Services();
        $metadata = new \CCMBenchmark\Ting\Repository\Metadata($services->get('SerializerFactory'));
        $metadata->setEntity('tests\fixtures\model\Bouh');

        $metadataRepository = new \CCMBenchmark\Ting\MetadataRepository($services->get('SerializerFactory'));
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
        $metadata = new \CCMBenchmark\Ting\Repository\Metadata($services->get('SerializerFactory'));
        $metadata->setEntity('tests\fixtures\model\Bouh');

        $metadataRepository = new \CCMBenchmark\Ting\MetadataRepository($services->get('SerializerFactory'));
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
        $metadata = new \CCMBenchmark\Ting\Repository\Metadata($services->get('SerializerFactory'));
        $metadata->setConnectionName('connectionName');
        $metadata->setDatabase('database');
        $metadata->setTable('T_BOUH_BOO');

        $metadataRepository = new \CCMBenchmark\Ting\MetadataRepository($services->get('SerializerFactory'));
        $metadataRepository->addMetadata('tests\fixtures\model\BouhRepository', $metadata);

        $this
            ->if($metadataRepository->findMetadataForTable(
                'connectionName',
                'database',
                '',
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
        $metadata = new \CCMBenchmark\Ting\Repository\Metadata($services->get('SerializerFactory'));
        $metadata->setTable('T_BOUH_BOO');

        $metadataRepository = new \CCMBenchmark\Ting\MetadataRepository($services->get('SerializerFactory'));
        $metadataRepository->addMetadata('tests\fixtures\model\BouhRepository', $metadata);

        $this
            ->if($metadataRepository->findMetadataForTable(
                'connectionName',
                'database',
                '',
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

    public function testFindMetadataForTableWithRightSchemaShouldCallCallbackFound()
    {
        $services = new \CCMBenchmark\Ting\Services();
        $metadata = new \CCMBenchmark\Ting\Repository\Metadata($services->get('SerializerFactory'));
        $metadata->setConnectionName('connectionName');
        $metadata->setDatabase('database');
        $metadata->setTable('T_BOUH_BOO');
        $metadata->setSchema('schemaName');

        $metadataRepository = new \CCMBenchmark\Ting\MetadataRepository($services->get('SerializerFactory'));
        $metadataRepository->addMetadata('tests\fixtures\model\BouhRepository', $metadata);

        $this
            ->if($metadataRepository->findMetadataForTable(
                'connectionName',
                'database',
                'schemaName',
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

    public function testFindMetadataForTableWithWrongSchemaShouldCallCallbackNotFound()
    {
        $services = new \CCMBenchmark\Ting\Services();
        $metadata = new \CCMBenchmark\Ting\Repository\Metadata($services->get('SerializerFactory'));
        $metadata->setTable('T_BOUH_BOO');
        $metadata->setSchema('SchemaName');

        $metadataRepository = new \CCMBenchmark\Ting\MetadataRepository($services->get('SerializerFactory'));
        $metadataRepository->addMetadata('tests\fixtures\model\BouhRepository', $metadata);

        $this
            ->if($metadataRepository->findMetadataForTable(
                'connectionName',
                'database',
                'otherSchema',
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

    public function testBatchLoadMetadataShouldCallInitMetadataWithDefaultOptions()
    {
        $services = new \CCMBenchmark\Ting\Services();
        $this
            ->if($metadataRepository = $services->get('MetadataRepository'))
            ->then($metadataRepository->batchLoadMetadata(
                'tests\fixtures\model',
                __DIR__ . '/../../fixtures/model/*Repository.php',
                ['default' => ['connection' => 'connectionName', 'database' => 'databaseName']]
            ))
            ->object($bouhRepository = $services->get('RepositoryFactory')->get('\tests\fixtures\model\BouhRepository'))
            ->array($bouhRepository::$options)
                ->isIdenticalTo(['connection' => 'connectionName', 'database' => 'databaseName']);
    }

    public function testBatchLoadMetadataShouldCallInitMetadataWithDefaultAndRepositoryOptions()
    {
        $services = new \CCMBenchmark\Ting\Services();
        $this
            ->if($metadataRepository = $services->get('MetadataRepository'))
            ->then($metadataRepository->batchLoadMetadata(
                'tests\fixtures\model',
                __DIR__ . '/../../fixtures/model/*Repository.php',
                [
                    'default' => ['connection' => 'connectionName', 'database' => 'databaseName'],
                    'tests\fixtures\model\BouhRepository' => ['database' => 'dbBouh']
                ]
            ))
            ->object($bouhRepository = $services->get('RepositoryFactory')->get('\tests\fixtures\model\BouhRepository'))
            ->array($bouhRepository::$options)
                ->isIdenticalTo(['connection' => 'connectionName', 'database' => 'dbBouh']);
    }

    public function testBatchLoadMetadataShouldCallInitMetadataWithRepositoryOptions()
    {
        $services = new \CCMBenchmark\Ting\Services();
        $this
            ->if($metadataRepository = $services->get('MetadataRepository'))
            ->then($metadataRepository->batchLoadMetadata(
                'tests\fixtures\model',
                __DIR__ . '/../../fixtures/model/*Repository.php',
                ['tests\fixtures\model\BouhRepository' => ['connection' => 'conBouh', 'database' => 'dbBouh']]
            ))
            ->object($bouhRepository = $services->get('RepositoryFactory')->get('\tests\fixtures\model\BouhRepository'))
            ->array($bouhRepository::$options)
            ->isIdenticalTo(['connection' => 'conBouh', 'database' => 'dbBouh']);
    }

    public function testBatchLoadMetadataShouldLoad5Repositories()
    {
        $services = new \CCMBenchmark\Ting\Services();
        $this
            ->if(
                $metadataRepository = new \CCMBenchmark\Ting\MetadataRepository(
                    $services->get('SerializerFactory')
                )
            )
            ->array($metadataRepository->batchLoadMetadata(
                'tests\fixtures\model',
                __DIR__ . '/../../fixtures/model/*Repository.php'
            ))
                ->isIdenticalTo([
                    'tests\fixtures\model\BouhMySchemaRepository' => 'tests\fixtures\model\BouhMySchemaRepository',
                    'tests\fixtures\model\BouhRepository'         => 'tests\fixtures\model\BouhRepository',
                    'tests\fixtures\model\CityRepository'         => 'tests\fixtures\model\CityRepository',
                    'tests\fixtures\model\CitySecondRepository'   => 'tests\fixtures\model\CitySecondMetadataRepository',
                    'tests\fixtures\model\ParkRepository'         => 'tests\fixtures\model\ParkRepository'
                ]);
    }

    public function testBatchLoadMetadataWithInvalidPathShouldReturnEmptyArray()
    {
        $services = new \CCMBenchmark\Ting\Services();
        $this
            ->if(
                $metadataRepository = new \CCMBenchmark\Ting\MetadataRepository(
                    $services->get('SerializerFactory')
                )
            )
            ->array($metadataRepository->batchLoadMetadata(
                'tests\fixtures\model',
                '/not/valid/path/*Repository.php'
            ))
                ->isEmpty()
        ;
    }

    public function testBatchLoadMetadataFromCacheShouldLoad1Repository()
    {
        $paths = ['tests\fixtures\model\BouhRepository'];
        $services = new \CCMBenchmark\Ting\Services();
        $this
            ->if(
                $metadataRepository = new \CCMBenchmark\Ting\MetadataRepository(
                    $services->get('SerializerFactory')
                )
            )
            ->array($metadataRepository->batchLoadMetadataFromCache($paths))
                ->size
                    ->isIdenticalTo(1)
        ;
    }

    public function testBatchLoadMetadataForRepositoryWhichNotImplementMetadataInitializerShouldDoNothing()
    {
        $services = new \CCMBenchmark\Ting\Services();
        $this
            ->if(
                $metadataRepository = new \CCMBenchmark\Ting\MetadataRepository(
                    $services->get('SerializerFactory')
                )
            )
            ->array($metadataRepository->batchLoadMetadata(
                'tests\fixtures\model',
                '/not/valid/path/NoMetadataRepository.php'
            ))
                ->isEmpty();
    }

    public function testFindMetadataForOtherConnectionShouldCallCallbackNotFound()
    {
        $services = new \CCMBenchmark\Ting\Services();
        $metadata = new \CCMBenchmark\Ting\Repository\Metadata($services->get('SerializerFactory'));
        $metadata->setEntity('tests\fixtures\model\Bouh');
        $metadata->setConnectionName('connection2');
        $metadata->setDatabase('bouh_world');
        $metadata->setTable('bouh');

        $metadataRepository = new \CCMBenchmark\Ting\MetadataRepository($services->get('SerializerFactory'));
        $metadataRepository->addMetadata('tests\fixtures\model\BouhRepository', $metadata);

        $this
            ->if($metadataRepository->findMetadataForTable(
                'connection',
                'bouh_world',
                '',
                'bouh',
                function ($metadata) use (&$outerCallbackFound) {
                    $outerCallbackFound = true;
                },
                function () use (&$outerCallbackNotFound) {
                    $outerCallbackNotFound = true;
                }
            ))
            ->variable($outerCallbackFound)
                ->isNull()
            ->boolean($outerCallbackNotFound)
                ->isTrue();
    }

    public function testFindMetadataForOtherDatabaseShouldFailbackAndCallCallbackFound()
    {
        $services = new \CCMBenchmark\Ting\Services();
        $metadata = new \CCMBenchmark\Ting\Repository\Metadata($services->get('SerializerFactory'));
        $metadata->setEntity('tests\fixtures\model\Bouh');
        $metadata->setConnectionName('connection');
        $metadata->setDatabase('bouh_world_2');
        $metadata->setTable('bouh');

        $metadataRepository = new \CCMBenchmark\Ting\MetadataRepository($services->get('SerializerFactory'));
        $metadataRepository->addMetadata('tests\fixtures\model\BouhRepository', $metadata);

        $this
            ->if($metadataRepository->findMetadataForTable(
                'connection',
                'bouh_world',
                '',
                'bouh',
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

    public function testFindMetadataForRightDatabaseShouldCallCallbackFound()
    {
        $services = new \CCMBenchmark\Ting\Services();

        $metadataRepository = new \CCMBenchmark\Ting\MetadataRepository($services->get('SerializerFactory'));

        $metadata = new \CCMBenchmark\Ting\Repository\Metadata($services->get('SerializerFactory'));
        $metadata->setEntity('tests\fixtures\model\Bouh');
        $metadata->setConnectionName('connection');
        $metadata->setDatabase('bouh_world');
        $metadata->setTable('bouh');
        $metadataRepository->addMetadata('tests\fixtures\model\BouhRepository', $metadata);

        $metadata = new \CCMBenchmark\Ting\Repository\Metadata($services->get('SerializerFactory'));
        $metadata->setEntity('tests\fixtures\model\Bouh2');
        $metadata->setConnectionName('connection');
        $metadata->setDatabase('bouh_world_2');
        $metadata->setTable('bouh');
        $metadataRepository->addMetadata('tests\fixtures\model\BouhRepository', $metadata);

        $this
            ->if($metadataRepository->findMetadataForTable(
                'connection',
                'bouh_world_2',
                '',
                'bouh',
                function ($metadata) use (&$outerMetadata, &$outerCallbackFound) {
                    $outerMetadata = $metadata;
                    $outerCallbackFound = true;
                },
                function () use (&$outerCallbackNotFound) {
                    $outerCallbackNotFound = true;
                }
            ))
            ->boolean($outerCallbackFound)
                ->isTrue()
            ->variable($outerCallbackNotFound)
                ->isNull()
            ->variable($outerMetadata->getEntity())
                ->isIdenticalTo('tests\fixtures\model\Bouh2');
    }
}
