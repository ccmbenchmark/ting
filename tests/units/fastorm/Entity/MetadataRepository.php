<?php

namespace tests\units\fastorm\Entity;

use \mageekguy\atoum;

class MetadataRepository extends atoum
{
    public function testShouldBeSingleton()
    {
        $this
            ->object(\fastorm\Entity\MetadataRepository::getInstance())
            ->isIdenticalTo(\fastorm\Entity\MetadataRepository::getInstance());
    }

    public function testCallLoadMetadataTwiceShouldCallCallbackWithInstanceMetadata()
    {
        $repository = \tests\fixtures\model\BouhRepository::getInstance();

        $this
            ->if($metadataRepository = \fastorm\Entity\MetadataRepository::getInstance())
            ->then($metadataRepository->loadMetadata($repository, function ($repository) use (&$outerRepository) {
                $outerRepository = $repository;
            }))
            ->object($outerRepository)
                ->isInstanceOf('\fastorm\Entity\Metadata');
    }

    public function testCallLoadMetadataTwiceShouldCallCallbackWithSameMetadataObject()
    {
        $repository = \tests\fixtures\model\BouhRepository::getInstance();

        $this
            ->if($metadataRepository = \fastorm\Entity\MetadataRepository::getInstance())
            ->then($metadataRepository->loadMetadata($repository, function ($repository) use (&$outerRepository) {
                $outerRepository = $repository;
            }))
            ->then($metadataRepository->loadMetadata($repository, function ($repository) use (&$outerRepository2) {
                $outerRepository2 = $repository;
            }))
            ->object($outerRepository2)
                ->isIdenticalTo($outerRepository);
    }

    public function testFindMetadataForEntityShouldCallCallbackFound()
    {
        $metadata = new \fastorm\Entity\Metadata();
        $metadata->setClass('tests\fixtures\model\BouhRepository');

        $metadataRepository = \fastorm\Entity\MetadataRepository::getInstance();
        $metadata->addInto($metadataRepository);

        $entity = new \tests\fixtures\model\Bouh();

        $this
            ->if($metadataRepository = \fastorm\Entity\MetadataRepository::getInstance())
            ->then($metadataRepository->findMetadataForEntity(
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
        $metadata = new \fastorm\Entity\Metadata();
        $metadata->setClass('tests\fixtures\model\BouhRepository');

        $metadataRepository = \fastorm\Entity\MetadataRepository::getInstance();
        $metadata->addInto($metadataRepository);

        $entity = new \mock\tests\fixtures\model\Bouh2();

        $this
            ->if($metadataRepository = \fastorm\Entity\MetadataRepository::getInstance())
            ->then($metadataRepository->findMetadataForEntity(
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
        $metadata = new \fastorm\Entity\Metadata();
        $metadata->setTable('T_BOUH_BOO');

        $metadataRepository = \fastorm\Entity\MetadataRepository::getInstance();
        $metadata->addInto($metadataRepository);

        $this
            ->if($metadataRepository = \fastorm\Entity\MetadataRepository::getInstance())
            ->then($metadataRepository->findMetadataForTable(
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
        $metadata = new \fastorm\Entity\Metadata();
        $metadata->setTable('T_BOUH_BOO');

        $metadataRepository = \fastorm\Entity\MetadataRepository::getInstance();
        $metadata->addInto($metadataRepository);

        $this
            ->if($metadataRepository = \fastorm\Entity\MetadataRepository::getInstance())
            ->then($metadataRepository->findMetadataForTable(
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
            ->if($metadataRepository = \fastorm\Entity\MetadataRepository::getInstance())
            ->variable($return = $metadataRepository->batchLoadMetadata(
                'tests\fixtures\model',
                __DIR__ . '/../../../fixtures/model/*Repository.php'
            ))
                ->isIdenticalTo(1);
    }

    public function testBatchLoadMetadataWithInvalidPathShouldReturn0()
    {
        $this
            ->if($metadataRepository = \fastorm\Entity\MetadataRepository::getInstance())
            ->variable($return = $metadataRepository->batchLoadMetadata(
                'tests\fixtures\model',
                '/not/valid/path/*Repository.php'
            ))
                ->isIdenticalTo(0);
    }
}
