<?php

namespace tests\units\fastorm;

use \mageekguy\atoum;

class UnitOfWork extends atoum
{
    public function beforeTestMethod()
    {
        \fastorm\ConnectionPool::getInstance(
            array(
                'connections' => array(
                    'main' => array(
                        'namespace' => '\tests\fixtures\FakeDriver',
                        'host'      => 'localhost.test',
                        'user'      => 'test',
                        'password'  => 'test',
                        'port'      => 3306
                    )
                )
            )
        );

        $metadataRepository = \fastorm\Entity\MetadataRepository::getInstance();
        $metadataRepository->batchLoadMetadata('tests\fixtures\model', __DIR__ . '/../../fixtures/model/*Repository.php');
    }

    public function testShouldBeSingleton()
    {
        $this
            ->object(\fastorm\UnitOfWork::getInstance())
            ->isIdenticalTo(\fastorm\UnitOfWork::getInstance());
    }

    public function testManageShouldAddProperyListener()
    {
        $mockEntity = new \mock\tests\fixtures\model\Bouh();
        $this->calling($mockEntity)->addPropertyListener = function ($unitOfWork) use (&$outerUnitOfWork) {
            $outerUnitOfWork = $unitOfWork;
        };

        $this
            ->if($unitOfWork = \fastorm\UnitOfWork::getInstance())
            ->then($unitOfWork->manage($mockEntity))
            ->object($outerUnitOfWork)
                ->IsIdenticalTo($unitOfWork)
            ->boolean($unitOfWork->isManaged($mockEntity))
                ->isTrue();
    }

    public function testIsManagedShouldReturnFalse()
    {
        $mockEntity = new \mock\tests\fixtures\model\Bouh();

        $this
            ->if($unitOfWork = \fastorm\UnitOfWork::getInstance())
            ->boolean($unitOfWork->isManaged($mockEntity))
                ->isFalse();
    }

    public function testPersist()
    {
        $mockEntity = new \mock\tests\fixtures\model\Bouh();

        $this
            ->if($unitOfWork = \fastorm\UnitOfWork::getInstance())
            ->then($unitOfWork->persist($mockEntity))
            ->boolean($unitOfWork->isPersisted($mockEntity))
                ->isTrue()
            ->boolean($unitOfWork->isNew($mockEntity))
                ->isTrue();
    }

    public function testIsPersistedShouldReturnFalse()
    {
        $mockEntity = new \mock\tests\fixtures\model\Bouh();

        $this
            ->if($unitOfWork = \fastorm\UnitOfWork::getInstance())
            ->boolean($unitOfWork->isPersisted($mockEntity))
                ->isFalse();
    }

    public function testPersistManagedEntityShouldNotMarkedNew()
    {
        $mockEntity = new \mock\tests\fixtures\model\Bouh();

        $this
            ->if($unitOfWork = \fastorm\UnitOfWork::getInstance())
            ->then($unitOfWork->manage($mockEntity))
            ->then($unitOfWork->persist($mockEntity))
            ->boolean($unitOfWork->isPersisted($mockEntity))
                ->isTrue()
            ->boolean($unitOfWork->isNew($mockEntity))
                ->isFalse();
    }

    public function testPropertyChangedShouldDoNothing()
    {
        $mockEntity = new \mock\tests\fixtures\model\Bouh();

        $this
            ->if($unitOfWork = \fastorm\UnitOfWork::getInstance())
            ->then($unitOfWork->propertyChanged($mockEntity, 'firstname', 'Sylvain', 'Sylvain'))
            ->boolean($unitOfWork->isPropertyChanged($mockEntity, 'firstname'))
                ->isFalse();
    }

    public function testPropertyChangedShouldMarkedChanged()
    {
        $mockEntity = new \mock\tests\fixtures\model\Bouh();

        $this
            ->if($unitOfWork = \fastorm\UnitOfWork::getInstance())
            ->then($unitOfWork->propertyChanged($mockEntity, 'firstname', 'Sylvain', 'Sylvain 2'))
            ->boolean($unitOfWork->isPropertyChanged($mockEntity, 'firstname'))
                ->isTrue();
    }

    public function testDetach()
    {
        $mockEntity = new \mock\tests\fixtures\model\Bouh();

        $this
            ->if($unitOfWork = \fastorm\UnitOfWork::getInstance())
            ->then($unitOfWork->persist($mockEntity))
            ->boolean($unitOfWork->isPersisted($mockEntity))
                ->isTrue()
            ->then($unitOfWork->detach($mockEntity))
            ->boolean($unitOfWork->isPersisted($mockEntity))
                ->isFalse();
    }

    public function testRemove()
    {
        $mockEntity = new \mock\tests\fixtures\model\Bouh();

        $this
            ->if($unitOfWork = \fastorm\UnitOfWork::getInstance())
            ->then($unitOfWork->remove($mockEntity))
            ->boolean($unitOfWork->isRemoved($mockEntity))
                ->isTrue();
    }

    public function testRemoveShouldReturnFalse()
    {
        $mockEntity = new \mock\tests\fixtures\model\Bouh();

        $this
            ->if($unitOfWork = \fastorm\UnitOfWork::getInstance())
            ->boolean($unitOfWork->isRemoved($mockEntity))
                ->isFalse();
    }

    public function testFlushShouldCallFlushManaged()
    {
        $mockMetadata = new \mock\fastorm\Entity\Metadata();
        \tests\fixtures\model\BouhRepository::initMetadata(null, $mockMetadata);

        $outerOid = array();
        $this->calling($mockMetadata)->generateQueryForUpdate =
            function($driver, $entity, $properties, $callback) use (&$outerOid) {
                $outerOid[] = spl_object_hash($entity);
                $callback(new \fastorm\Query(""));
            };

        $mockMetadataRepository = new \mock\fastorm\Entity\MetadataRepository();
        $this->calling($mockMetadataRepository)->findMetadataForEntity =
            function ($entity, $callback) use ($mockMetadata) {
                $callback($mockMetadata);
            };

        $entity1 = new \mock\tests\fixtures\model\Bouh();
        $entity2 = new \mock\tests\fixtures\model\Bouh();
        $entity3 = new \mock\tests\fixtures\model\Bouh();

        $this
            ->if($unitOfWork = \fastorm\UnitOfWork::getInstance())
            ->then($unitOfWork->manage($entity1))
            ->then($unitOfWork->manage($entity2))
            ->then($unitOfWork->manage($entity3))
            ->then($entity1->setName('Bouh 1'))
            ->then($entity2->setName('Bouh 2'))
            ->then($entity3->setName('Bouh 3'))
            ->then($unitOfWork->persist($entity1))
            ->then($unitOfWork->persist($entity2))
            ->then($unitOfWork->persist($entity3))
            ->then($unitOfWork->flush($mockMetadataRepository))
            ->boolean($unitOfWork->isPersisted($entity1))
                ->isFalse()
            ->boolean($unitOfWork->isPersisted($entity2))
                ->isFalse()
            ->boolean($unitOfWork->isPersisted($entity3))
                ->isFalse()
            ->array($outerOid)
                ->isIdenticalTo(array(
                    spl_object_hash($entity1),
                    spl_object_hash($entity2),
                    spl_object_hash($entity3)
            ));
        }

        public function testFlushShouldCallFlushManagedButDoNothing()
        {
            $mockMetadata = new \mock\fastorm\Entity\Metadata();
            \tests\fixtures\model\BouhRepository::initMetadata(null, $mockMetadata);

            $outerOid = array();
            $this->calling($mockMetadata)->generateQueryForUpdate =
                function($driver, $entity, $properties, $callback) use (&$outerOid) {
                    $outerOid[] = spl_object_hash($entity);
                };

            $mockMetadataRepository = new \mock\fastorm\Entity\MetadataRepository();
            $this->calling($mockMetadataRepository)->findMetadataForEntity =
                function ($entity, $callback) use ($mockMetadata) {
                    $callback($mockMetadata);
                };

            $entity1 = new \mock\tests\fixtures\model\Bouh();
            $entity2 = new \mock\tests\fixtures\model\Bouh();
            $entity3 = new \mock\tests\fixtures\model\Bouh();
            $entity2->setName('Bouh');

            $this
                ->if($unitOfWork = \fastorm\UnitOfWork::getInstance())
                ->then($unitOfWork->detach($entity1))
                ->then($unitOfWork->detach($entity2))
                ->then($unitOfWork->detach($entity3))
                ->then(function () {
                    unset($outerOId);
                })
                ->then($outerOid = array())
                ->then($unitOfWork->manage($entity1))
                ->then($unitOfWork->manage($entity2))
                ->then($unitOfWork->manage($entity3))
                ->then($entity2->setName('Bidule'))
                ->then($entity2->setName('Bouh')) // Set the default value
                ->then($unitOfWork->persist($entity1))
                ->then($unitOfWork->persist($entity2))
                ->then($unitOfWork->persist($entity3))
                ->then($unitOfWork->flush($mockMetadataRepository))
                ->array($outerOid)
                    ->isIdenticalTo(array());
    }

    public function testFlushShouldCallFlushNew()
    {
        $mockMetadata = new \mock\fastorm\Entity\Metadata();
        \tests\fixtures\model\BouhRepository::initMetadata(null, $mockMetadata);

        $mockQuery = new \mock\fastorm\Query("");
        $this->calling($mockQuery)->execute = 3;

        $outerOid = array();
        $this->calling($mockMetadata)->generateQueryForInsert =
            function($driver, $entity, $callback) use (&$outerOid, $mockQuery) {
                $outerOid[] = spl_object_hash($entity);
                $callback($mockQuery);
            };

        $mockMetadataRepository = new \mock\fastorm\Entity\MetadataRepository();
        $this->calling($mockMetadataRepository)->findMetadataForEntity =
            function ($entity, $callback) use ($mockMetadata) {
                $callback($mockMetadata);
            };

        $entity1 = new \tests\fixtures\model\Bouh();
        $entity2 = new \tests\fixtures\model\Bouh();
        $entity3 = new \tests\fixtures\model\Bouh();

        $this
            ->if($unitOfWork = \fastorm\UnitOfWork::getInstance())
            ->then($entity1->setName('Bouh 1'))
            ->then($entity2->setName('Bouh 2'))
            ->then($entity3->setName('Bouh 3'))
            ->then($unitOfWork->persist($entity1))
            ->then($unitOfWork->persist($entity2))
            ->then($unitOfWork->persist($entity3))
            ->then($unitOfWork->flush($mockMetadataRepository))
            ->mock($mockMetadata)
                ->call('setEntityPrimary')->exactly(3)
            ->boolean($unitOfWork->isManaged($entity1))
                ->isTrue()
            ->boolean($unitOfWork->isManaged($entity2))
                ->isTrue()
            ->boolean($unitOfWork->isManaged($entity2))
                ->isTrue()
            ->boolean($unitOfWork->isPersisted($entity1))
                ->isFalse()
            ->boolean($unitOfWork->isPersisted($entity2))
                ->isFalse()
            ->boolean($unitOfWork->isPersisted($entity2))
                ->isFalse()
            ->array($outerOid)
                ->isIdenticalTo(array(
                    spl_object_hash($entity1),
                    spl_object_hash($entity2),
                    spl_object_hash($entity3)
            ));
    }

    public function testFlushShouldCallFlushDelete()
    {
        $mockMetadata = new \mock\fastorm\Entity\Metadata();
        \tests\fixtures\model\BouhRepository::initMetadata(null, $mockMetadata);

        $outerOid = array();
        $this->calling($mockMetadata)->generateQueryForDelete =
            function($driver, $entity, $callback) use (&$outerOid) {
                $outerOid[] = spl_object_hash($entity);
                $callback(new \fastorm\Query(""));
            };

        $mockMetadataRepository = new \mock\fastorm\Entity\MetadataRepository();
        $this->calling($mockMetadataRepository)->findMetadataForEntity =
            function ($entity, $callback) use ($mockMetadata) {
                $callback($mockMetadata);
            };

        $entity1 = new \tests\fixtures\model\Bouh();
        $entity2 = new \tests\fixtures\model\Bouh();
        $entity3 = new \tests\fixtures\model\Bouh();

        $this
            ->if($unitOfWork = \fastorm\UnitOfWork::getInstance())
            ->then($unitOfWork->remove($entity1))
            ->then($unitOfWork->remove($entity2))
            ->then($unitOfWork->remove($entity3))
            ->then($unitOfWork->flush($mockMetadataRepository))
            ->boolean($unitOfWork->isPersisted($entity1))
                ->isFalse()
            ->boolean($unitOfWork->isPersisted($entity2))
                ->isFalse()
            ->boolean($unitOfWork->isPersisted($entity3))
                ->isFalse()
            ->array($outerOid)
                ->isIdenticalTo(array(
                    spl_object_hash($entity1),
                    spl_object_hash($entity2),
                    spl_object_hash($entity3)
            ));
    }
}
