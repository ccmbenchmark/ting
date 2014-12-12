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

namespace tests\units\CCMBenchmark\Ting\Repository;

use mageekguy\atoum;
use tests\fixtures\model\Bouh;

class Metadata extends atoum
{
    public function testGetConnection()
    {
        $mockConnectionPool = new \mock\CCMBenchmark\Ting\ConnectionPool();

        $services = new \CCMBenchmark\Ting\Services();
        $this
            ->if($metadata = new \CCMBenchmark\Ting\Repository\Metadata($services->get('QueryFactory')))
            ->object($metadata->getConnection($mockConnectionPool))
                ->isInstanceOf('\CCMBenchmark\Ting\Connection')
        ;
    }

    public function testSetDatabaseShouldReturnThis()
    {
        $services = new \CCMBenchmark\Ting\Services();
        $this
            ->if($metadata = new \CCMBenchmark\Ting\Repository\Metadata($services->get('QueryFactory')))
            ->object($metadata->setDatabase('myDatabase'))
                ->isIdenticalTo($metadata)
        ;
    }

    public function testSetConnectionShouldReturnThis()
    {
        $services = new \CCMBenchmark\Ting\Services();
        $this
            ->if($metadata = new \CCMBenchmark\Ting\Repository\Metadata($services->get('QueryFactory')))
            ->object($metadata->setConnectionName('main'))
                ->isIdenticalTo($metadata)
        ;
    }

    public function testSetEntityShouldRaiseExceptionWhenStartWithSlash()
    {
        $services = new \CCMBenchmark\Ting\Services();
        $this
            ->if($metadata = new \CCMBenchmark\Ting\Repository\Metadata($services->get('QueryFactory')))
            ->exception(function () use ($metadata) {
                    $metadata->setEntity('\my\namespace\Bouh');
            })
                ->hasMessage('Class must not start with a \\');
    }

    public function testAddFieldShouldReturnThis()
    {
        $services = new \CCMBenchmark\Ting\Services();
        $this
            ->if($metadata = new \CCMBenchmark\Ting\Repository\Metadata($services->get('QueryFactory')))
            ->object($metadata->addField(array('columnName' => 'BO_BOUH', 'fieldName' => 'bouh')))
                ->isIdenticalTo($metadata);
    }

    public function testAddFieldWithInvalidParametersShouldThrowException()
    {
        $services = new \CCMBenchmark\Ting\Services();
        $this
            ->if($metadata = new \CCMBenchmark\Ting\Repository\Metadata($services->get('QueryFactory')))
            ->exception(function () use ($metadata) {
                $metadata->addField(array('fieldName' => 'bouh'));
            })
                ->hasDefaultCode()
                ->hasMessage('Field configuration must have fieldName and columnName properties');
    }

    public function testIfTableKnownShouldCallCallbackAndReturnTrue()
    {
        $services = new \CCMBenchmark\Ting\Services();
        $this
            ->if($metadata = new \CCMBenchmark\Ting\Repository\Metadata($services->get('QueryFactory')))
            ->then($metadata->setTable('Bouh'))
            ->boolean($metadata->ifTableKnown('Bouh', function ($metadata) use (&$outerMetadata) {
                $outerMetadata = $metadata;
            }))
                ->isTrue()
            ->object($outerMetadata)
                ->isIdenticalTo($metadata);
    }

    public function testIfTableKnownShouldReturnFalse()
    {
        $services = new \CCMBenchmark\Ting\Services();
        $this
            ->if($metadata = new \CCMBenchmark\Ting\Repository\Metadata($services->get('QueryFactory')))
            ->then($metadata->setTable('Bouh'))
            ->boolean($metadata->ifTableKnown(
                'Bim',
                function () {
                }
            ))
                ->isFalse();
    }

    public function testHasColumnShouldReturnTrue()
    {
        $services = new \CCMBenchmark\Ting\Services();
        $this
            ->if($metadata = new \CCMBenchmark\Ting\Repository\Metadata($services->get('QueryFactory')))
            ->then($metadata->setTable('Bouh'))
            ->then($metadata->addField(array('fieldName' => 'Bouh', 'columnName' => 'boo_bouh')))
            ->boolean($metadata->hasColumn('boo_bouh'))
                ->isTrue();
    }

    public function testHasColumnShouldReturnFalse()
    {
        $services = new \CCMBenchmark\Ting\Services();
        $this
            ->if($metadata = new \CCMBenchmark\Ting\Repository\Metadata($services->get('QueryFactory')))
            ->then($metadata->setTable('Bouh'))
            ->then($metadata->addField(array('fieldName' => 'Bouh', 'columnName' => 'BOO_bouh')))
            ->boolean($metadata->hasColumn('boo_no'))
                ->isFalse();
    }

    public function testCreateEntityShouldReturnObject()
    {
        $services = new \CCMBenchmark\Ting\Services();
        $this
            ->if($metadata = new \CCMBenchmark\Ting\Repository\Metadata($services->get('QueryFactory')))
            ->then($metadata->setEntity('mock\repository\Bouh'))
            ->object($bouh = $metadata->createEntity())
                ->isInstanceOf('\mock\repository\Bouh');
    }

    public function testSetEntityProperty()
    {
        $services = new \CCMBenchmark\Ting\Services();
        $metadata = new \CCMBenchmark\Ting\Repository\Metadata($services->get('QueryFactory'));
        $metadata->setEntity('mock\repository\Bouh');
        $metadata->addField(array(
            'fieldName'  => 'name',
            'columnName' => 'boo_name'
        ));

        $bouh = $metadata->createEntity();
        $this->calling($bouh)->setName = function ($name) {
            $this->name = $name;
        };

        $this
            ->if($metadata->setEntityProperty($bouh, 'boo_name', 'Sylvain'))
            ->string($bouh->name)
                ->isIdenticalTo('Sylvain');
    }

    public function testSetEntityPropertyForAutoIncrement()
    {
        $services = new \CCMBenchmark\Ting\Services();
        $metadata = new \CCMBenchmark\Ting\Repository\Metadata($services->get('QueryFactory'));
        $metadata->setEntity('mock\repository\Bouh');
        $metadata->addField(array(
            'primary'       => true,
            'autoincrement' => true,
            'fieldName'     => 'id',
            'columnName'    => 'boo_id'
        ));

        $bouh = $metadata->createEntity();
        $this->calling($bouh)->setId = function ($id) {
            $this->id = $id;
        };

        $this
            ->if($metadata->setEntityPropertyForAutoIncrement($bouh, 321))
            ->integer($bouh->id)
                ->isIdenticalTo(321);
    }

    public function testSetEntityPropertyForAutoIncrementWithoutAutoIncrementColumnShouldReturnFalse()
    {
        $services = new \CCMBenchmark\Ting\Services();
        $metadata = new \CCMBenchmark\Ting\Repository\Metadata($services->get('QueryFactory'));
        $metadata->setEntity('mock\repository\Bouh');
        $metadata->addField(array(
            'primary'    => true,
            'fieldName'  => 'id',
            'columnName' => 'boo_id'
        ));

        $bouh = $metadata->createEntity();
        $this->calling($bouh)->setId = function ($id) {
            $this->id = $id;
        };

        $this
            ->boolean($metadata->setEntityPropertyForAutoIncrement($bouh, 321))
                ->isFalse();
    }

    public function testGetByPrimariesShouldRaiseExceptionIfIncorrectPrimaries()
    {
        $mockConnectionPool = new \mock\CCMBenchmark\Ting\ConnectionPool();
        $this->calling($mockConnectionPool)->slave = new \tests\fixtures\FakeDriver\Driver();
        $mockConnection = new \mock\CCMBenchmark\Ting\Connection($mockConnectionPool, 'main', 'db');

        $services = new \CCMBenchmark\Ting\Services();
        $this
            ->if($metadata = new \CCMBenchmark\Ting\Repository\Metadata($services->get('QueryFactory')))
            ->and($metadata->setEntity('mock\repository\Bouh'))
            ->and(
                $metadata->addField([
                    'primary'    => true,
                    'fieldName'  => 'id',
                    'columnName' => 'boo_id'
                ])
            )
            ->and(
                $metadata->addField([
                    'primary'    => true,
                    'fieldName'  => 'secondId',
                    'columnName' => 'wonderful_id'
                ])
            )
            ->exception(
                function () use ($metadata, $mockConnection, $services) {
                    $metadata->getByPrimaries(
                        $mockConnection,
                        $services->get('QueryFactory'),
                        $services->get('CollectionFactory'),
                        1
                    );
                }
            )
                ->isInstanceOf('CCMBenchmark\Ting\Exception')
                ->hasMessage('Incorrect format for primaries')
        ;
    }

    public function testGetByPrimariesShouldReturnAQuery()
    {
        $mockConnectionPool = new \mock\CCMBenchmark\Ting\ConnectionPool();
        $this->calling($mockConnectionPool)->slave = new \tests\fixtures\FakeDriver\Driver();
        $mockConnection = new \mock\CCMBenchmark\Ting\Connection($mockConnectionPool, 'main', 'db');

        $services = new \CCMBenchmark\Ting\Services();
        $this
            ->if($metadata = new \CCMBenchmark\Ting\Repository\Metadata($services->get('QueryFactory')))
            ->and($metadata->setEntity('mock\repository\Bouh'))
            ->and(
                $metadata->addField([
                    'primary'    => true,
                    'fieldName'  => 'id',
                    'columnName' => 'boo_id'
                ])
            )
            ->object(
                $metadata->getByPrimaries(
                    $mockConnection,
                    $services->get('QueryFactory'),
                    $services->get('CollectionFactory'),
                    ['id' => 1]
                )
            )
                ->isInstanceOf('CCMBenchmark\Ting\Query\Query')
            ->object(
                $metadata->getByPrimaries(
                    $mockConnection,
                    $services->get('QueryFactory'),
                    $services->get('CollectionFactory'),
                    1
                )
            )
                ->isInstanceOf('CCMBenchmark\Ting\Query\Query')
        ;
    }

    public function testGenerateQueryForInsertShouldReturnAPreparedQuery()
    {
        $mockConnectionPool = new \mock\CCMBenchmark\Ting\ConnectionPool();
        $this->calling($mockConnectionPool)->master = new \tests\fixtures\FakeDriver\Driver();
        $mockConnection = new \mock\CCMBenchmark\Ting\Connection($mockConnectionPool, 'main', 'db');

        $entity = new Bouh();
        $entity->setName('Xavier');

        $services = new \CCMBenchmark\Ting\Services();
        $this
            ->if($metadata = new \CCMBenchmark\Ting\Repository\Metadata($services->get('QueryFactory')))
            ->and($metadata->setEntity('mock\repository\Bouh'))
            ->and(
                $metadata->addField([
                    'primary'    => true,
                    'fieldName'  => 'id',
                    'columnName' => 'boo_id'
                ])
            )
            ->object($metadata->generateQueryForInsert($mockConnection, $services->get('QueryFactory'), $entity))
                ->isInstanceOf('CCMBenchmark\Ting\Query\PreparedQuery')
        ;
    }

    public function testGenerateQueryForUpdateShouldReturnAPreparedQuery()
    {
        $mockConnectionPool = new \mock\CCMBenchmark\Ting\ConnectionPool();
        $this->calling($mockConnectionPool)->master = new \tests\fixtures\FakeDriver\Driver();
        $mockConnection = new \mock\CCMBenchmark\Ting\Connection($mockConnectionPool, 'main', 'db');

        $entity = new Bouh();
        $entity->setName('Xavier');

        $services = new \CCMBenchmark\Ting\Services();
        $this
            ->if($metadata = new \CCMBenchmark\Ting\Repository\Metadata($services->get('QueryFactory')))
            ->and($metadata->setEntity('mock\repository\Bouh'))
            ->and(
                $metadata->addField([
                    'primary'    => true,
                    'fieldName'  => 'id',
                    'columnName' => 'boo_id'
                ])
            )
            ->and(
                $metadata->addField([
                    'primary'    => true,
                    'fieldName'  => 'name',
                    'columnName' => 'firstname'
                ])
            )
            ->object(
                $metadata->generateQueryForUpdate(
                    $mockConnection,
                    $services->get('QueryFactory'),
                    $entity,
                    ['name' => 'Sylvain']
                )
            )
                ->isInstanceOf('CCMBenchmark\Ting\Query\PreparedQuery')
        ;
    }

    public function testGenerateQueryForDeleteShouldReturnAPreparedQuery()
    {
        $mockConnectionPool = new \mock\CCMBenchmark\Ting\ConnectionPool();
        $this->calling($mockConnectionPool)->master = new \tests\fixtures\FakeDriver\Driver();
        $mockConnection = new \mock\CCMBenchmark\Ting\Connection($mockConnectionPool, 'main', 'db');

        $entity = new Bouh();
        $entity->setName('Xavier');

        $services = new \CCMBenchmark\Ting\Services();
        $this
            ->if($metadata = new \CCMBenchmark\Ting\Repository\Metadata($services->get('QueryFactory')))
            ->and($metadata->setEntity('mock\repository\Bouh'))
            ->and(
                $metadata->addField([
                    'primary'    => true,
                    'fieldName'  => 'id',
                    'columnName' => 'boo_id'
                ])
            )
            ->object(
                $metadata->generateQueryForDelete($mockConnection, $services->get('QueryFactory'), ['id' => 1], $entity)
            )
                ->isInstanceOf('CCMBenchmark\Ting\Query\PreparedQuery')
        ;
    }
}
