<?php

namespace tests\units\fastorm\Entity;

use \mageekguy\atoum;

class MetadataRepository extends atoum
{
    public function testFindMetadataForEntityShouldCallCallbackFound()
    {
        $serviceLocator = new \fastorm\ServiceLocator();

        $metadata = new \fastorm\Entity\Metadata($serviceLocator);
        $metadata->setClass('tests\fixtures\model\BouhRepository');

        $metadataRepository = new \fastorm\Entity\MetadataRepository($serviceLocator);
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
        $serviceLocator = new \fastorm\ServiceLocator();

        $metadata = new \fastorm\Entity\Metadata($serviceLocator);
        $metadata->setClass('tests\fixtures\model\BouhRepository');

        $metadataRepository = new \fastorm\Entity\MetadataRepository($serviceLocator);
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
        $serviceLocator = new \fastorm\ServiceLocator();

        $metadata = new \fastorm\Entity\Metadata($serviceLocator);
        $metadata->setTable('T_BOUH_BOO');

        $metadataRepository = new \fastorm\Entity\MetadataRepository($serviceLocator);
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
        $serviceLocator = new \fastorm\ServiceLocator();

        $metadata = new \fastorm\Entity\Metadata($serviceLocator);
        $metadata->setTable('T_BOUH_BOO');

        $metadataRepository = new \fastorm\Entity\MetadataRepository($serviceLocator);
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
