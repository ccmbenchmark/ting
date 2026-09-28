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

use atoum;

class UnitOfWork extends atoum
{
    protected $services = null;

    public function beforeTestMethod($method)
    {
        $this->services       = new \CCMBenchmark\Ting\Services();
        $connectionPool = new \CCMBenchmark\Ting\ConnectionPool();
        $connectionPool->setConfig(
            [
                'main' => [
                    'namespace' => '\tests\fixtures\FakeDriver',
                    'master'    => [
                        'host'      => 'localhost.test',
                        'user'      => 'test',
                        'password'  => 'test',
                        'port'      => 3306
                    ]
                ]
            ]
        );

        $this->services->set('ConnectionPool', fn ($container) => $connectionPool);
    }

    public function testManageShouldAddPropertyListener()
    {
        $mockEntity = new \mock\tests\fixtures\model\Bouh();
        $this->calling($mockEntity)->addPropertyListener = function ($unitOfWork) use (&$outerUnitOfWork): void {
            $outerUnitOfWork = $unitOfWork;
        };

        $this
            ->if($unitOfWork = new \CCMBenchmark\Ting\UnitOfWork(
                $this->services->get('ConnectionPool'),
                $this->services->get('MetadataRepository'),
                $this->services->get('QueryFactory')
            ))
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
            ->if($unitOfWork = new \CCMBenchmark\Ting\UnitOfWork(
                $this->services->get('ConnectionPool'),
                $this->services->get('MetadataRepository'),
                $this->services->get('QueryFactory')
            ))
            ->boolean($unitOfWork->isManaged($mockEntity))
                ->isFalse();
    }

    public function testSave()
    {
        $mockEntity = new \mock\tests\fixtures\model\Bouh();

        $this
            ->if($unitOfWork = new \CCMBenchmark\Ting\UnitOfWork(
                $this->services->get('ConnectionPool'),
                $this->services->get('MetadataRepository'),
                $this->services->get('QueryFactory')
            ))
            ->then($unitOfWork->pushSave($mockEntity))
            ->boolean($unitOfWork->shouldBePersisted($mockEntity))
                ->isTrue()
            ->boolean($unitOfWork->isNew($mockEntity))
                ->isTrue();
    }

    public function testIsPersistedShouldReturnFalse()
    {
        $mockEntity = new \mock\tests\fixtures\model\Bouh();

        $this
            ->if($unitOfWork = new \CCMBenchmark\Ting\UnitOfWork(
                $this->services->get('ConnectionPool'),
                $this->services->get('MetadataRepository'),
                $this->services->get('QueryFactory')
            ))
            ->boolean($unitOfWork->shouldBePersisted($mockEntity))
                ->isFalse();
    }

    public function testPersistManagedEntityShouldNotMarkedNew()
    {
        $mockEntity = new \mock\tests\fixtures\model\Bouh();

        $this
            ->if($unitOfWork = new \CCMBenchmark\Ting\UnitOfWork(
                $this->services->get('ConnectionPool'),
                $this->services->get('MetadataRepository'),
                $this->services->get('QueryFactory')
            ))
            ->then($unitOfWork->manage($mockEntity))
            ->then($unitOfWork->pushSave($mockEntity))
            ->boolean($unitOfWork->shouldBePersisted($mockEntity))
                ->isTrue()
            ->boolean($unitOfWork->isNew($mockEntity))
                ->isFalse();
    }

    public function testIsPropertyChangedWithUUIDShouldReturnFalse()
    {
        $mockEntity = new \mock\tests\fixtures\model\Bouh();

        $this
            ->if($unitOfWork = new \CCMBenchmark\Ting\UnitOfWork(
                $this->services->get('ConnectionPool'),
                $this->services->get('MetadataRepository'),
                $this->services->get('QueryFactory')
            ))
            ->then($unitOfWork->propertyChanged($mockEntity, 'firstname', 'Sylvain', 'Sylvain'))
            ->boolean($unitOfWork->isPropertyChanged($mockEntity, 'firstname'))
                ->isFalse();
    }

    public function testPropertyChangedShouldDoNothing()
    {
        $mockEntity = new \mock\tests\fixtures\model\Bouh();

        $this
            ->if($unitOfWork = new \CCMBenchmark\Ting\UnitOfWork(
                $this->services->get('ConnectionPool'),
                $this->services->get('MetadataRepository'),
                $this->services->get('QueryFactory')
            ))
            ->then($unitOfWork->propertyChanged($mockEntity, 'firstname', 'Sylvain', 'Sylvain'))
            ->boolean($unitOfWork->isPropertyChanged($mockEntity, 'firstname'))
                ->isFalse();
    }

    public function testPropertyChangedShouldMarkedChanged()
    {
        $mockEntity = new \mock\tests\fixtures\model\Bouh();

        $this
            ->if($unitOfWork = new \CCMBenchmark\Ting\UnitOfWork(
                $this->services->get('ConnectionPool'),
                $this->services->get('MetadataRepository'),
                $this->services->get('QueryFactory')
            ))
            ->then($unitOfWork->propertyChanged($mockEntity, 'firstname', 'Sylvain', 'Sylvain 2'))
            ->boolean($unitOfWork->isPropertyChanged($mockEntity, 'firstname'))
                ->isTrue();
    }

    public function testDetach()
    {
        $mockEntity = new \mock\tests\fixtures\model\Bouh();

        $this
            ->if($unitOfWork = new \CCMBenchmark\Ting\UnitOfWork(
                $this->services->get('ConnectionPool'),
                $this->services->get('MetadataRepository'),
                $this->services->get('QueryFactory')
            ))
            ->then($unitOfWork->pushSave($mockEntity))
            ->boolean($unitOfWork->shouldBePersisted($mockEntity))
                ->isTrue()
            ->then($unitOfWork->detach($mockEntity))
            ->boolean($unitOfWork->shouldBePersisted($mockEntity))
                ->isFalse();
    }

    public function testDetachAll()
    {
        $entity1 = new \tests\fixtures\model\Bouh();
        $entity2 = new \tests\fixtures\model\Bouh();

        $this
            ->if($unitOfWork = new \CCMBenchmark\Ting\UnitOfWork(
                $this->services->get('ConnectionPool'),
                $this->services->get('MetadataRepository'),
                $this->services->get('QueryFactory')
            ))
            ->then($unitOfWork->pushSave($entity1))
            ->then($unitOfWork->pushSave($entity2))
            ->then($unitOfWork->detachAll())
            ->boolean($unitOfWork->shouldBePersisted($entity1))
                ->isFalse()
            ->boolean($unitOfWork->shouldBePersisted($entity2))
                ->isFalse();
    }

    public function testShouldBeRemovedWithUUIDShouldReturnFalse()
    {
        $mockEntity = new \mock\tests\fixtures\model\Bouh();

        $this
            ->if($unitOfWork = new \CCMBenchmark\Ting\UnitOfWork(
                $this->services->get('ConnectionPool'),
                $this->services->get('MetadataRepository'),
                $this->services->get('QueryFactory')
            ))
            ->boolean($unitOfWork->shouldBeRemoved($mockEntity))
                ->isFalse();
    }

    public function testRemove()
    {
        $mockEntity = new \mock\tests\fixtures\model\Bouh();

        $this
            ->if($unitOfWork = new \CCMBenchmark\Ting\UnitOfWork(
                $this->services->get('ConnectionPool'),
                $this->services->get('MetadataRepository'),
                $this->services->get('QueryFactory')
            ))
            ->then($unitOfWork->pushDelete($mockEntity))
            ->boolean($unitOfWork->shouldBeRemoved($mockEntity))
                ->isTrue();
    }

    public function testRemoveShouldReturnFalse()
    {
        $mockEntity = new \mock\tests\fixtures\model\Bouh();

        $this
            ->if($unitOfWork = new \CCMBenchmark\Ting\UnitOfWork(
                $this->services->get('ConnectionPool'),
                $this->services->get('MetadataRepository'),
                $this->services->get('QueryFactory')
            ))
            ->boolean($unitOfWork->shouldBeRemoved($mockEntity))
                ->isFalse();
    }

    public function testIsNewWithoutUUIDShouldReturnFalse()
    {
        $mockEntity = new \mock\tests\fixtures\model\Bouh();

        $this
            ->if($unitOfWork = new \CCMBenchmark\Ting\UnitOfWork(
                $this->services->get('ConnectionPool'),
                $this->services->get('MetadataRepository'),
                $this->services->get('QueryFactory')
            ))
            ->boolean($unitOfWork->isNew($mockEntity))
                ->isFalse();
    }

    public function testIsNewAfterProcessShouldReturnFalse()
    {
        $entity = new \tests\fixtures\model\Bouh();
        $mockMetadataRepository = new \CCMBenchmark\Ting\MetadataRepository($this->services->get('SerializerFactory'));

        $mockMetadataRepository->addMetadata(
            'tests\fixtures\model\BouhRepository',
            \tests\fixtures\model\BouhRepository::initMetadata($this->services->get('SerializerFactory'))
        );

        $mockConnectionPool = new \mock\CCMBenchmark\Ting\ConnectionPool();
        $mockConnection = new \mock\CCMBenchmark\Ting\Connection($mockConnectionPool, 'main', 'db');

        $mockQueryFactory = new \mock\CCMBenchmark\Ting\Query\QueryFactory();

        $mockPreparedQuery = new \mock\CCMBenchmark\Ting\Query\PreparedQuery('', $mockConnection);
        $this->calling($mockPreparedQuery)->prepareExecute = $mockPreparedQuery;
        $this->calling($mockPreparedQuery)->execute = true;

        $mockDriver = new \mock\CCMBenchmark\Ting\Driver\Mysqli\Driver();
        $this->calling($mockDriver)->getInsertedId = 1;
        $this->calling($mockDriver)->closeStatement = true;

        $this->calling($mockQueryFactory)->getPrepared = $mockPreparedQuery;
        $this->calling($mockConnectionPool)->master = $mockDriver;

        $this
            ->if($unitOfWork = new \CCMBenchmark\Ting\UnitOfWork(
                $mockConnectionPool,
                $mockMetadataRepository,
                $mockQueryFactory
            ))
            ->then($unitOfWork->pushSave($entity))
            ->boolean($unitOfWork->shouldBePersisted($entity))
                ->isTrue()
            ->boolean($unitOfWork->isNew($entity))
                ->isTrue()
            ->variable($unitOfWork->process())
                ->isNull()
            ->boolean($unitOfWork->shouldBePersisted($entity))
                ->isFalse()
            ->boolean($unitOfWork->isNew($entity))
                ->isFalse()
        ;
    }

    public function testShouldBePersistedAfterProcessShouldReturnFalse()
    {
        $entity = new \tests\fixtures\model\Bouh();
        $mockMetadataRepository = new \CCMBenchmark\Ting\MetadataRepository($this->services->get('SerializerFactory'));

        $mockMetadataRepository->addMetadata(
            'tests\fixtures\model\BouhRepository',
            \tests\fixtures\model\BouhRepository::initMetadata($this->services->get('SerializerFactory'))
        );

        $mockConnectionPool = new \mock\CCMBenchmark\Ting\ConnectionPool();
        $mockConnection = new \mock\CCMBenchmark\Ting\Connection($mockConnectionPool, 'main', 'db');

        $mockQueryFactory = new \mock\CCMBenchmark\Ting\Query\QueryFactory();

        $mockPreparedQuery = new \mock\CCMBenchmark\Ting\Query\PreparedQuery('', $mockConnection);
        $this->calling($mockPreparedQuery)->prepareExecute = $mockPreparedQuery;
        $this->calling($mockPreparedQuery)->execute = true;

        $mockDriver = new \mock\CCMBenchmark\Ting\Driver\Mysqli\Driver();
        $this->calling($mockDriver)->getInsertedId = 1;
        $this->calling($mockDriver)->closeStatement = true;


        $this->calling($mockQueryFactory)->getPrepared = $mockPreparedQuery;
        $this->calling($mockConnectionPool)->master = $mockDriver;

        $this
            ->if($unitOfWork = new \CCMBenchmark\Ting\UnitOfWork(
                $mockConnectionPool,
                $mockMetadataRepository,
                $mockQueryFactory
            ))
            ->and($unitOfWork->manage($entity))
            ->then($entity->setName('newName'))
            ->then($unitOfWork->pushSave($entity))
            ->boolean($unitOfWork->shouldBePersisted($entity))
                ->isTrue()
            ->variable($unitOfWork->process())
                ->isNull()
            ->boolean($unitOfWork->shouldBePersisted($entity))
                ->isFalse()
            ->then($unitOfWork->pushDelete($entity))
            ->boolean($unitOfWork->shouldBePersisted($entity))
                ->isTrue()
            ->then($entity->setId(1))
            ->variable($unitOfWork->process())
                ->isNull()
            ->boolean($unitOfWork->shouldBePersisted($entity))
                ->isFalse()
        ;
    }

    public function testTryingToProcessAnEntityWithoutRepositoryShouldRaiseAnException()
    {
        $entity = new \tests\fixtures\model\Bouh();
        $mockMetadataRepository = new \CCMBenchmark\Ting\MetadataRepository($this->services->get('SerializerFactory'));

        $mockConnectionPool = new \mock\CCMBenchmark\Ting\ConnectionPool();
        $mockConnection = new \mock\CCMBenchmark\Ting\Connection($mockConnectionPool, 'main', 'db');

        $mockQueryFactory = new \mock\CCMBenchmark\Ting\Query\QueryFactory();

        $mockPreparedQuery = new \mock\CCMBenchmark\Ting\Query\PreparedQuery('', $mockConnection);
        $this->calling($mockPreparedQuery)->prepareExecute = $mockPreparedQuery;
        $this->calling($mockPreparedQuery)->execute = true;

        $mockDriver = new \mock\CCMBenchmark\Ting\Driver\Mysqli\Driver();
        $this->calling($mockDriver)->getInsertedId = 1;


        $this->calling($mockQueryFactory)->getPrepared = $mockPreparedQuery;
        $this->calling($mockConnectionPool)->master = $mockDriver;

        $this
            ->if($unitOfWork = new \CCMBenchmark\Ting\UnitOfWork(
                $mockConnectionPool,
                $mockMetadataRepository,
                $mockQueryFactory
            ))
            ->then($unitOfWork->pushSave($entity))
            ->exception(function () use ($unitOfWork): void {
                $unitOfWork->process();
            })
                ->isInstanceOf(\CCMBenchmark\Ting\Exception::class)
            ->then($unitOfWork->pushDelete($entity))
            ->exception(function () use ($unitOfWork): void {
                $unitOfWork->process();
            })
                ->isInstanceOf(\CCMBenchmark\Ting\Exception::class)
            ->and($unitOfWork->manage($entity))
            ->then($entity->setName('newName'))
            ->then($unitOfWork->pushSave($entity))
            ->exception(function () use ($unitOfWork): void {
                $unitOfWork->process();
            })
                ->isInstanceOf(\CCMBenchmark\Ting\Exception::class)
        ;
    }

    private function newUnitOfWork(): \CCMBenchmark\Ting\UnitOfWork
    {
        return new \CCMBenchmark\Ting\UnitOfWork(
            $this->services->get('ConnectionPool'),
            $this->services->get('MetadataRepository'),
            $this->services->get('QueryFactory')
        );
    }

    /**
     * Copie d'une entite chargee en base : son enregistrement est un UPDATE, pas un INSERT.
     */
    public function testCopyOfAManagedEntityShouldBeSavedAsManaged()
    {
        $entity = new \tests\fixtures\model\Bouh();

        $this
            ->if($unitOfWork = $this->newUnitOfWork())
            ->and($unitOfWork->manage($entity))
            ->and($copy = clone $entity)
            ->then($unitOfWork->pushSave($copy))
            ->boolean($unitOfWork->isManaged($copy))
                ->isTrue()
            ->boolean($unitOfWork->isNew($copy))
                ->isFalse()
        ;
    }

    /**
     * Une modification faite sur l'original avant la copie est enregistree avec la copie.
     */
    public function testCopyShouldKeepTheChangesMadeBeforeTheCopy()
    {
        $entity = new \tests\fixtures\model\Bouh();

        $this
            ->if($unitOfWork = $this->newUnitOfWork())
            ->and($unitOfWork->manage($entity))
            ->and($entity->setName('avant la copie'))
            ->and($copy = clone $entity)
            ->and($copy->setFirstname('apres la copie'))
            ->boolean($unitOfWork->isPropertyChanged($copy, 'name'))
                ->isTrue()
            ->boolean($unitOfWork->isPropertyChanged($copy, 'firstname'))
                ->isTrue()
        ;
    }

    public function testCopyOfANewEntityShouldStayNew()
    {
        $entity = new \tests\fixtures\model\Bouh();

        $this
            ->if($unitOfWork = $this->newUnitOfWork())
            ->and($copy = clone $entity)
            ->then($unitOfWork->pushSave($copy))
            ->boolean($unitOfWork->isNew($copy))
                ->isTrue()
        ;
    }

    /**
     * PHP reutilise le numero d'un objet libere : la copie ne se rattache pas a une entite
     * d'une autre classe qui a recu ce numero.
     */
    public function testCopyShouldNotBeAdoptedByAnEntityOfAnotherClassReusingTheOriginalId()
    {
        $unitOfWork = $this->newUnitOfWork();
        $entity = new \tests\fixtures\model\Bouh();
        $unitOfWork->manage($entity);
        $copy = clone $entity;
        $originId = spl_object_id($entity);
        unset($entity);
        $other = new \tests\fixtures\model\City();
        $unitOfWork->manage($other);

        $this
            ->integer(spl_object_id($other))
                ->isIdenticalTo($originId)
            ->then($unitOfWork->pushSave($copy))
            ->boolean($unitOfWork->isNew($copy))
                ->isTrue()
        ;
    }

    public function testDetachShouldReleaseTheCopiesToo()
    {
        $entity = new \tests\fixtures\model\Bouh();

        $this
            ->if($unitOfWork = $this->newUnitOfWork())
            ->and($unitOfWork->manage($entity))
            ->and($copy = clone $entity)
            ->then($unitOfWork->detach($entity))
            ->boolean($unitOfWork->isManaged($copy))
                ->isFalse()
        ;
    }

    /**
     * L'identite ne vaut que le temps de la requete : une entite relue depuis un cache n'est pas geree.
     */
    public function testUnserializedEntityShouldNotBeManaged()
    {
        $entity = new \tests\fixtures\model\Bouh();

        $this
            ->if($unitOfWork = $this->newUnitOfWork())
            ->and($unitOfWork->manage($entity))
            ->array($entity->__serialize())
                ->notHasKey('tingOriginId')
            ->boolean($unitOfWork->isManaged(unserialize(serialize($entity))))
                ->isFalse()
        ;
    }

    /**
     * Une entite qui n'utilise pas NotifyProperty est suivie par l'objet lui-meme.
     */
    public function testEntityWithoutTheTraitShouldBeTrackedByObject()
    {
        $entity = new \mock\CCMBenchmark\Ting\Entity\NotifyPropertyInterface();

        $this
            ->if($unitOfWork = $this->newUnitOfWork())
            ->and($unitOfWork->manage($entity))
            ->boolean($unitOfWork->isManaged($entity))
                ->isTrue()
            ->boolean($unitOfWork->isManaged(clone $entity))
                ->isFalse()
        ;
    }
}
