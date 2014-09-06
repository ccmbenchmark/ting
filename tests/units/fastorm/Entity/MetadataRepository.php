<?php

namespace tests\units\fastorm\Entity;

use \mageekguy\atoum;

class MetadataRepository extends atoum
{
    public function testCallLoadMetadataShouldCallCallbackWithInstanceMetadata()
    {
        $serviceLocator = new \fastorm\ServiceLocator();

        $this
            ->if($metadataRepository = $serviceLocator->get('MetadataRepository'))
            ->then($metadataRepository->loadMetadata(
                'tests\fixtures\model\BouhRepository',
                function ($metadata) use (&$outerMetadata) {
                    $outerMetadata = $metadata;
                }
            ))
            ->object($outerMetadata)
                ->isInstanceOf('\fastorm\Entity\Metadata');
    }

    public function testCallLoadMetadataTwiceShouldCallCallbackWithSameMetadataObject()
    {
        $serviceLocator = new \fastorm\ServiceLocator();

        $this
            ->if($metadataRepository = $serviceLocator->get('MetadataRepository'))
            ->then($metadataRepository->loadMetadata(
                'tests\fixtures\model\BouhRepository',
                function ($metadata) use (&$outerMetadata) {
                    $outerMetadata = $metadata;
                }
            ))
            ->then($metadataRepository->loadMetadata(
                'tests\fixtures\model\BouhRepository',
                function ($metadata) use (&$outerMetadata2) {
                    $outerMetadata2 = $metadata;
                }
            ))
            ->object($outerMetadata)
                ->isIdenticalTo($outerMetadata);
    }

    public function testFindMetadataForEntityShouldCallCallbackFound()
    {
        $serviceLocator = new \fastorm\ServiceLocator();

        $metadata = new \fastorm\Entity\Metadata($serviceLocator);
        $metadata->setClass('tests\fixtures\model\BouhRepository');

        $metadataRepository = new \fastorm\Entity\MetadataRepository($serviceLocator);
        $metadata->addInto($metadataRepository);

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
        $serviceLocator = new \fastorm\ServiceLocator();

        $metadata = new \fastorm\Entity\Metadata($serviceLocator);
        $metadata->setClass('tests\fixtures\model\BouhRepository');

        $metadataRepository = new \fastorm\Entity\MetadataRepository($serviceLocator);
        $metadata->addInto($metadataRepository);

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
        $serviceLocator = new \fastorm\ServiceLocator();

        $metadata = new \fastorm\Entity\Metadata($serviceLocator);
        $metadata->setTable('T_BOUH_BOO');

        $metadataRepository = new \fastorm\Entity\MetadataRepository($serviceLocator);
        $metadata->addInto($metadataRepository);

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
        $serviceLocator = new \fastorm\ServiceLocator();

        $metadata = new \fastorm\Entity\Metadata($serviceLocator);
        $metadata->setTable('T_BOUH_BOO');

        $metadataRepository = new \fastorm\Entity\MetadataRepository($serviceLocator);
        $metadata->addInto($metadataRepository);

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
        $this
            ->if($metadataRepository = new \fastorm\Entity\MetadataRepository(new \fastorm\ServiceLocator()))
            ->variable($return = $metadataRepository->batchLoadMetadata(
                'tests\fixtures\model',
                __DIR__ . '/../../../fixtures/model/*Repository.php'
            ))
                ->isIdenticalTo(1);
    }

    public function testBatchLoadMetadataWithInvalidPathShouldReturn0()
    {
        $this
            ->if($metadataRepository = new \fastorm\Entity\MetadataRepository(new \fastorm\ServiceLocator()))
            ->variable($return = $metadataRepository->batchLoadMetadata(
                'tests\fixtures\model',
                '/not/valid/path/*Repository.php'
            ))
                ->isIdenticalTo(0);
    }
}
