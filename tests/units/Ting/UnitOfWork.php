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

        $this->services->set('ConnectionPool', function ($container) use ($connectionPool) {
            return $connectionPool;
        });
    }

    public function testManageShouldAddPropertyListener()
    {
        $mockEntity = new \mock\tests\fixtures\model\Bouh();
        $this->calling($mockEntity)->addPropertyListener = function ($unitOfWork) use (&$outerUnitOfWork) {
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
            ->then($unitOfWork->save($mockEntity))
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
            ->then($unitOfWork->save($mockEntity))
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
            ->then($unitOfWork->save($mockEntity))
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
                $this->services->get('MetadataRepository'),
                $this->services->get('QueryFactory')
            ))
            ->then($unitOfWork->delete($mockEntity))
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
}
