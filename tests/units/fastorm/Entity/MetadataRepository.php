<?php

namespace tests\units\fastorm\Entity;

use \mageekguy\atoum;

class MetadataRepository extends atoum
{
    public function testFindMetadataForEntityShouldCallCallbackFound()
    {
        $services = new \fastorm\Services();
        $metadata = new \fastorm\Entity\Metadata($services->get('QueryFactory'));
        $metadata->setClass('tests\fixtures\model\BouhRepository');

        $metadataRepository = new \fastorm\Entity\MetadataRepository($services->get('MetadataFactory'));
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
        $services = new \fastorm\Services();
        $metadata = new \fastorm\Entity\Metadata($services->get('QueryFactory'));
        $metadata->setClass('tests\fixtures\model\BouhRepository');

        $metadataRepository = new \fastorm\Entity\MetadataRepository($services->get('MetadataFactory'));
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
        $services = new \fastorm\Services();
        $metadata = new \fastorm\Entity\Metadata($services->get('QueryFactory'));
        $metadata->setTable('T_BOUH_BOO');

        $metadataRepository = new \fastorm\Entity\MetadataRepository($services->get('MetadataFactory'));
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
        $services = new \fastorm\Services();
        $metadata = new \fastorm\Entity\Metadata($services->get('QueryFactory'));
        $metadata->setTable('T_BOUH_BOO');

        $metadataRepository = new \fastorm\Entity\MetadataRepository($services->get('MetadataFactory'));
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
        $services = new \fastorm\Services();
        $this
            ->if($metadataRepository = new \fastorm\Entity\MetadataRepository($services->get('MetadataFactory')))
            ->variable($return = $metadataRepository->batchLoadMetadata(
                'tests\fixtures\model',
                __DIR__ . '/../../../fixtures/model/*Repository.php'
            ))
                ->isIdenticalTo(1);
    }

    public function testBatchLoadMetadataWithInvalidPathShouldReturn0()
    {
        $services = new \fastorm\Services();
        $this
            ->if($metadataRepository = new \fastorm\Entity\MetadataRepository($services->get('MetadataFactory')))
            ->variable($return = $metadataRepository->batchLoadMetadata(
                'tests\fixtures\model',
                '/not/valid/path/*Repository.php'
            ))
                ->isIdenticalTo(0);
    }
}
