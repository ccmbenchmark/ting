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
                    'host'      => 'localhost.test',
                    'user'      => 'test',
                    'password'  => 'test',
                    'port'      => 3306
                ]
            ]
        );

        $this->services->set('ConnectionPool', function ($container) use ($connectionPool) {
            return $connectionPool;
        });
    }

    public function testManageShouldAddProperyListener()
    {
        $mockEntity = new \mock\tests\fixtures\model\Bouh();
        $this->calling($mockEntity)->addPropertyListener = function ($unitOfWork) use (&$outerUnitOfWork) {
            $outerUnitOfWork = $unitOfWork;
        };

        $this
            ->if($unitOfWork = new \CCMBenchmark\Ting\UnitOfWork(
                $this->services->get('ConnectionPool'),
                $this->services->get('MetadataRepository')
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
                $this->services->get('MetadataRepository')
            ))
            ->boolean($unitOfWork->isManaged($mockEntity))
                ->isFalse();
    }

    public function testPersist()
    {
        $mockEntity = new \mock\tests\fixtures\model\Bouh();

        $this
            ->if($unitOfWork = new \CCMBenchmark\Ting\UnitOfWork(
                $this->services->get('ConnectionPool'),
                $this->services->get('MetadataRepository')
            ))
            ->then($unitOfWork->persist($mockEntity))
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
                $this->services->get('MetadataRepository')
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
                $this->services->get('MetadataRepository')
            ))
            ->then($unitOfWork->manage($mockEntity))
            ->then($unitOfWork->persist($mockEntity))
            ->boolean($unitOfWork->shouldBePersisted($mockEntity))
                ->isTrue()
            ->boolean($unitOfWork->isNew($mockEntity))
                ->isFalse();
    }

    public function testPropertyChangedShouldDoNothing()
    {
        $mockEntity = new \mock\tests\fixtures\model\Bouh();

        $this
            ->if($unitOfWork = new \CCMBenchmark\Ting\UnitOfWork(
                $this->services->get('ConnectionPool'),
                $this->services->get('MetadataRepository')
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
                $this->services->get('MetadataRepository')
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
                $this->services->get('MetadataRepository')
            ))
            ->then($unitOfWork->persist($mockEntity))
            ->boolean($unitOfWork->shouldBePersisted($mockEntity))
                ->isTrue()
            ->then($unitOfWork->detach($mockEntity))
            ->boolean($unitOfWork->shouldBePersisted($mockEntity))
                ->isFalse();
    }

    public function testRemove()
    {
        $mockEntity = new \mock\tests\fixtures\model\Bouh();

        $this
            ->if($unitOfWork = new \CCMBenchmark\Ting\UnitOfWork(
                $this->services->get('ConnectionPool'),
                $this->services->get('MetadataRepository')
            ))
            ->then($unitOfWork->remove($mockEntity))
            ->boolean($unitOfWork->shouldBeRemoved($mockEntity))
                ->isTrue();
    }

    public function testRemoveShouldReturnFalse()
    {
        $mockEntity = new \mock\tests\fixtures\model\Bouh();

        $this
            ->if($unitOfWork = new \CCMBenchmark\Ting\UnitOfWork(
                $this->services->get('ConnectionPool'),
                $this->services->get('MetadataRepository')
            ))
            ->boolean($unitOfWork->shouldBeRemoved($mockEntity))
                ->isFalse();
    }

    public function testFlushShouldCallFlushManaged()
    {
        $mockMetadataRepository = new \mock\CCMBenchmark\Ting\MetadataRepository(
            $this->services->get('MetadataFactory')
        );
        $mockMetadata           = new \mock\CCMBenchmark\Ting\Repository\Metadata(
            $this->services->get('QueryFactory')
        );
        $mockMetadataFactory    = new \mock\CCMBenchmark\Ting\Repository\MetadataFactory(
            $this->services->get('QueryFactory')
        );

        $this->services->set('MetadataRepository', function ($container) use ($mockMetadataRepository) {
            return $mockMetadataRepository;
        });

        $this->calling($mockMetadataFactory)->get = $mockMetadata;

        $this->services->set('Metadata', function ($container) use ($mockMetadata) {
            return $mockMetadata;
        });

        \tests\fixtures\model\BouhRepository::initMetadata($mockMetadataFactory);

        $this->calling($mockMetadataRepository)->findMetadataForEntity =
            function ($entity, $callback) use ($mockMetadata) {
                return $callback($mockMetadata);
            };


        $outerOid = array();
        $mockPreparedQuery = new \mock\CCMBenchmark\Ting\Query\PreparedQuery(['sql' => '']);
        $this->calling($mockPreparedQuery)->execute = 3;

        $this->calling($mockMetadata)->generateQueryForUpdate =
            function ($driver, $entity, $properties, $callback) use (&$outerOid, $mockPreparedQuery) {
                $outerOid[] = spl_object_hash($entity);
                $callback($mockPreparedQuery);
            };

        $entity1 = new \mock\tests\fixtures\model\Bouh();
        $entity2 = new \mock\tests\fixtures\model\Bouh();
        $entity3 = new \mock\tests\fixtures\model\Bouh();

        $this
            ->if($unitOfWork = new \CCMBenchmark\Ting\UnitOfWork(
                $this->services->get('ConnectionPool'),
                $this->services->get('MetadataRepository')
            ))
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
            ->boolean($unitOfWork->shouldBePersisted($entity1))
                ->isFalse()
            ->boolean($unitOfWork->shouldBePersisted($entity2))
                ->isFalse()
            ->boolean($unitOfWork->shouldBePersisted($entity3))
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
        $mockMetadataRepository = new \mock\CCMBenchmark\Ting\MetadataRepository(
            $this->services->get('MetadataFactory')
        );
        $mockMetadata           = new \mock\CCMBenchmark\Ting\Repository\Metadata($this->services->get('QueryFactory'));

        $this->services->set('MetadataRepository', function ($container) use ($mockMetadataRepository) {
            return $mockMetadataRepository;
        });

        $this->services->set('Metadata', function ($container) use ($mockMetadata) {
            return $mockMetadata;
        });

        \tests\fixtures\model\BouhRepository::initMetadata($this->services->get('MetadataFactory'));

        $outerOid = array();
        $this->calling($mockMetadata)->generateQueryForUpdate =
            function ($driver, $entity, $properties, $callback) use (&$outerOid) {
                $outerOid[] = spl_object_hash($entity);
            };

        $this->calling($mockMetadataRepository)->findMetadataForEntity =
            function ($entity, $callback) use ($mockMetadata) {
                $callback($mockMetadata);
            };

        $entity1 = new \mock\tests\fixtures\model\Bouh();
        $entity2 = new \mock\tests\fixtures\model\Bouh();
        $entity3 = new \mock\tests\fixtures\model\Bouh();
        $entity2->setName('Bouh');

        $this
            ->if($unitOfWork = new \CCMBenchmark\Ting\UnitOfWork(
                $this->services->get('ConnectionPool'),
                $this->services->get('MetadataRepository')
            ))
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
        $mockMetadataRepository = new \mock\CCMBenchmark\Ting\MetadataRepository(
            $this->services->get('MetadataFactory')
        );
        $mockMetadata           = new \mock\CCMBenchmark\Ting\Repository\Metadata($this->services->get('QueryFactory'));
        $mockMetadataFactory    = new \mock\CCMBenchmark\Ting\Repository\MetadataFactory(
            $this->services->get('QueryFactory')
        );

        $this->services->set('MetadataRepository', function ($container) use ($mockMetadataRepository) {
            return $mockMetadataRepository;
        });

        $this->calling($mockMetadataFactory)->get = $mockMetadata;

        $this->services->set('Metadata', function ($container) use ($mockMetadata) {
            return $mockMetadata;
        });

        \tests\fixtures\model\BouhRepository::initMetadata($mockMetadataFactory);

        $mockQuery = new \mock\CCMBenchmark\Ting\Query\PreparedQuery(['sql' => '']);
        $this->calling($mockQuery)->execute = 3;

        $outerOid = array();
        $this->calling($mockMetadata)->generateQueryForInsert =
            function ($driver, $entity, $callback) use (&$outerOid, $mockQuery) {
                $outerOid[] = spl_object_hash($entity);
                $callback($mockQuery);
            };

        $this->calling($mockMetadataRepository)->findMetadataForEntity =
            function ($entity, $callback) use ($mockMetadata) {
                $callback($mockMetadata);
            };

        $entity1 = new \tests\fixtures\model\Bouh();
        $entity2 = new \tests\fixtures\model\Bouh();
        $entity3 = new \tests\fixtures\model\Bouh();

        $this
            ->if($unitOfWork = new \CCMBenchmark\Ting\UnitOfWork(
                $this->services->get('ConnectionPool'),
                $this->services->get('MetadataRepository')
            ))
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
            ->boolean($unitOfWork->shouldBePersisted($entity1))
                ->isFalse()
            ->boolean($unitOfWork->shouldBePersisted($entity2))
                ->isFalse()
            ->boolean($unitOfWork->shouldBePersisted($entity2))
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
        $mockMetadataRepository = new \mock\CCMBenchmark\Ting\MetadataRepository(
            $this->services->get('MetadataFactory')
        );
        $mockMetadata           = new \mock\CCMBenchmark\Ting\Repository\Metadata($this->services->get('QueryFactory'));
        $mockMetadataFactory    = new \mock\CCMBenchmark\Ting\Repository\MetadataFactory(
            $this->services->get('QueryFactory')
        );

        $this->services->set('MetadataRepository', function ($container) use ($mockMetadataRepository) {
            return $mockMetadataRepository;
        });

        $this->calling($mockMetadataFactory)->get = $mockMetadata;

        $this->services->set('Metadata', function ($container) use ($mockMetadata) {
            return $mockMetadata;
        });

        \tests\fixtures\model\BouhRepository::initMetadata($mockMetadataFactory);

        $mockPreparedQuery = new \mock\CCMBenchmark\Ting\Query\PreparedQuery(['sql' => '']);
        $this->calling($mockPreparedQuery)->execute = 3;

        $outerOid = array();
        $this->calling($mockMetadata)->generateQueryForDelete =
            function ($driver, $entity, $callback) use (&$outerOid, $mockPreparedQuery) {
                $outerOid[] = spl_object_hash($entity);
                $callback($mockPreparedQuery);
            };

        $this->calling($mockMetadataRepository)->findMetadataForEntity =
            function ($entity, $callback) use ($mockMetadata) {
                $callback($mockMetadata);
            };

        $entity1 = new \tests\fixtures\model\Bouh();
        $entity2 = new \tests\fixtures\model\Bouh();
        $entity3 = new \tests\fixtures\model\Bouh();

        $this
            ->if($unitOfWork = new \CCMBenchmark\Ting\UnitOfWork(
                $this->services->get('ConnectionPool'),
                $this->services->get('MetadataRepository')
            ))
            ->then($unitOfWork->remove($entity1))
            ->then($unitOfWork->remove($entity2))
            ->then($unitOfWork->remove($entity3))
            ->then($unitOfWork->flush($mockMetadataRepository))
            ->boolean($unitOfWork->shouldBePersisted($entity1))
                ->isFalse()
            ->boolean($unitOfWork->shouldBePersisted($entity2))
                ->isFalse()
            ->boolean($unitOfWork->shouldBePersisted($entity3))
                ->isFalse()
            ->array($outerOid)
                ->isIdenticalTo(array(
                    spl_object_hash($entity1),
                    spl_object_hash($entity2),
                    spl_object_hash($entity3)
            ));
    }
}
