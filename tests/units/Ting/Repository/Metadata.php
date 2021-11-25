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
use tests\fixtures\model\BouhCustomGetter;

class Metadata extends atoum
{
    public function testGetConnection()
    {
        $mockConnectionPool = new \mock\CCMBenchmark\Ting\ConnectionPool();

        $services = new \CCMBenchmark\Ting\Services();
        $this
            ->if($metadata = new \CCMBenchmark\Ting\Repository\Metadata($services->get('SerializerFactory')))
            ->and($metadata->setDatabase('myDatabase'))
            ->and($metadata->setConnectionName('myConnection'))
            ->object($metadata->getConnection($mockConnectionPool))
                ->isInstanceOf('\CCMBenchmark\Ting\Connection')
        ;
    }

    public function testGetConnectionName()
    {
        $services = new \CCMBenchmark\Ting\Services();
        $this
            ->if($metadata = new \CCMBenchmark\Ting\Repository\Metadata($services->get('SerializerFactory')))
            ->and($metadata->setDatabase('myDatabase'))
            ->and($metadata->setConnectionName('myConnection'))
            ->string($metadata->getConnectionName())
                ->isIdenticalTo('myConnection')
        ;
    }

    public function testGetSchema()
    {
        $services = new \CCMBenchmark\Ting\Services();
        $this
            ->if($metadata = new \CCMBenchmark\Ting\Repository\Metadata($services->get('SerializerFactory')))
            ->and($metadata->setDatabase('myDatabase'))
            ->and($metadata->setSchema('schemaName'))
            ->string($metadata->getSchema())
                ->isIdenticalTo('schemaName');
        ;
    }

    public function testSetRepositoryShouldRaiseExceptionWhenStartWithSlash()
    {
        $services = new \CCMBenchmark\Ting\Services();
        $this
            ->if($metadata = new \CCMBenchmark\Ting\Repository\Metadata($services->get('SerializerFactory')))
            ->exception(function () use ($metadata) {
                $metadata->setRepository('\my\namespace\Bouh');
            })
                ->hasMessage('Class must not start with a \\');
    }

    public function testGetRepository()
    {
        $services = new \CCMBenchmark\Ting\Services();
        $this
            ->if($metadata = new \CCMBenchmark\Ting\Repository\Metadata($services->get('SerializerFactory')))
            ->and($metadata->setRepository('myRepository'))
            ->string($metadata->getRepository())
                ->isIdenticalTo('myRepository')
        ;
    }

    public function testSetDatabaseShouldReturnThis()
    {
        $services = new \CCMBenchmark\Ting\Services();
        $this
            ->if($metadata = new \CCMBenchmark\Ting\Repository\Metadata($services->get('SerializerFactory')))
            ->object($metadata->setDatabase('myDatabase'))
                ->isIdenticalTo($metadata)
        ;
    }

    public function testSetConnectionShouldReturnThis()
    {
        $services = new \CCMBenchmark\Ting\Services();
        $this
            ->if($metadata = new \CCMBenchmark\Ting\Repository\Metadata($services->get('SerializerFactory')))
            ->object($metadata->setConnectionName('main'))
                ->isIdenticalTo($metadata)
        ;
    }

    public function testSetEntityShouldRaiseExceptionWhenStartWithSlash()
    {
        $services = new \CCMBenchmark\Ting\Services();
        $this
            ->if($metadata = new \CCMBenchmark\Ting\Repository\Metadata($services->get('SerializerFactory')))
            ->exception(function () use ($metadata) {
                    $metadata->setEntity('\my\namespace\Bouh');
            })
                ->hasMessage('Class must not start with a \\');
    }

    public function testAddFieldShouldReturnThis()
    {
        $services = new \CCMBenchmark\Ting\Services();
        $this
            ->if($metadata = new \CCMBenchmark\Ting\Repository\Metadata($services->get('SerializerFactory')))
            ->object($metadata->addField(array('columnName' => 'BO_BOUH', 'fieldName' => 'bouh', 'type' => 'string')))
                ->isIdenticalTo($metadata);
    }

    public function testGetFields()
    {
        $services = new \CCMBenchmark\Ting\Services();
        $this
            ->if($metadata = new \CCMBenchmark\Ting\Repository\Metadata($services->get('SerializerFactory')))
            ->and($metadata->addField(
                array('columnName' => 'user_firstname', 'fieldName' => 'firstname', 'type' => 'string'))
            )
            ->and($metadata->addField(
                array('columnName' => 'user_lastname', 'fieldName' => 'lastname', 'type' => 'string'))
            )
            ->array($metadata->getFields())
                ->isIdenticalTo([
                    [
                        'columnName' => 'user_firstname',
                        'fieldName'  => 'firstname',
                        'type'       => 'string'
                    ],
                    [
                        'columnName' => 'user_lastname',
                        'fieldName'  => 'lastname',
                        'type'       => 'string'
                    ]
                ]);
    }

    public function testAddFieldWithInvalidParametersShouldThrowException()
    {
        $services = new \CCMBenchmark\Ting\Services();
        $this
            ->if($metadata = new \CCMBenchmark\Ting\Repository\Metadata($services->get('SerializerFactory')))
            ->exception(function () use ($metadata) {
                $metadata->addField(array('fieldName' => 'bouh'));
            })
                ->hasDefaultCode()
                ->hasMessage('Field configuration must have "columnName" property')
            ->exception(function () use ($metadata) {
                $metadata->addField(array('columnName' => 'BOO_BOUH'));
            })
                ->hasDefaultCode()
                ->hasMessage('Field configuration must have "fieldName" property')
            ->exception(function () use ($metadata) {
                $metadata->addField(array('fieldName' => 'bouh', 'columnName' => 'BOO_BOUH'));
            })
                ->hasDefaultCode()
                ->hasMessage('Field configuration must have "type" property');
    }

    public function testIfTableKnownShouldCallCallbackAndReturnTrue()
    {
        $services = new \CCMBenchmark\Ting\Services();
        $this
            ->if($metadata = new \CCMBenchmark\Ting\Repository\Metadata($services->get('SerializerFactory')))
            ->then($metadata->setConnectionName('connectionName'))
            ->then($metadata->setDatabase('database'))
            ->then($metadata->setTable('Bouh'))
            ->boolean($metadata->ifTableKnown(
                'connectionName',
                'database',
                'Bouh',
                function ($metadata) use (&$outerMetadata) {
                    $outerMetadata = $metadata;
                }
            ))
                ->isTrue()
            ->object($outerMetadata)
                ->isIdenticalTo($metadata);
    }

    public function testIfTableKnownShouldReturnFalse()
    {
        $services = new \CCMBenchmark\Ting\Services();
        $this
            ->if($metadata = new \CCMBenchmark\Ting\Repository\Metadata($services->get('SerializerFactory')))
            ->then($metadata->setTable('Bouh'))
            ->boolean($metadata->ifTableKnown(
                'connectionName',
                'database',
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
            ->if($metadata = new \CCMBenchmark\Ting\Repository\Metadata($services->get('SerializerFactory')))
            ->then($metadata->setTable('Bouh'))
            ->then($metadata->addField(array('fieldName' => 'Bouh', 'columnName' => 'boo_bouh', 'type' => 'string')))
            ->boolean($metadata->hasColumn('boo_bouh'))
                ->isTrue();
    }

    public function testHasColumnShouldReturnFalse()
    {
        $services = new \CCMBenchmark\Ting\Services();
        $this
            ->if($metadata = new \CCMBenchmark\Ting\Repository\Metadata($services->get('SerializerFactory')))
            ->then($metadata->setTable('Bouh'))
            ->then($metadata->addField(array('fieldName' => 'Bouh', 'columnName' => 'BOO_bouh', 'type' => 'string')))
            ->boolean($metadata->hasColumn('boo_no'))
                ->isFalse();
    }

    public function testCreateEntityShouldReturnObject()
    {
        $services = new \CCMBenchmark\Ting\Services();
        $this
            ->if($metadata = new \CCMBenchmark\Ting\Repository\Metadata($services->get('SerializerFactory')))
            ->then($metadata->setEntity('mock\repository\Bouh'))
            ->object($bouh = $metadata->createEntity())
                ->isInstanceOf('\mock\repository\Bouh');
    }

    public function testSetEntityPropertyWithDefaultSetter()
    {
        $services = new \CCMBenchmark\Ting\Services();
        $metadata = new \CCMBenchmark\Ting\Repository\Metadata($services->get('SerializerFactory'));
        $metadata->setEntity('mock\repository\Bouh');
        $metadata->addField(array(
            'fieldName'  => 'name',
            'columnName' => 'boo_name',
            'type'       => 'string'
        ));

        $bouh = $metadata->createEntity();
        $this->calling($bouh)->setName = function ($name) {
            $this->name = $name;
        };

        $this
            ->if($metadata->setEntityProperty($bouh, 'boo_name', 'Sylvain'))
            ->string($bouh->name)
                ->isIdenticalTo('Sylvain')
            ->mock($bouh)
                ->call('setName')
                    ->once();
    }

    public function testSetEntityPropertyShouldKeepNull()
    {
        $services = new \CCMBenchmark\Ting\Services();
        $metadata = new \CCMBenchmark\Ting\Repository\Metadata($services->get('SerializerFactory'));
        $metadata->setEntity('mock\repository\Bouh');
        $metadata->addField(array(
            'fieldName'  => 'old',
            'columnName' => 'boo_old',
            'type'       => 'int'
        ));

        $bouh = $metadata->createEntity();
        $this->calling($bouh)->setOld = function ($old) {
            $this->old = $old;
        };

        $this
            ->if($metadata->setEntityProperty($bouh, 'boo_old', null))
            ->variable($bouh->old)
                ->isIdenticalTo(null);
    }

    public function testSetEntityPropertyShouldCastToInt()
    {
        $services = new \CCMBenchmark\Ting\Services();
        $metadata = new \CCMBenchmark\Ting\Repository\Metadata($services->get('SerializerFactory'));
        $metadata->setEntity('mock\repository\Bouh');
        $metadata->addField(array(
            'fieldName'  => 'old',
            'columnName' => 'boo_old',
            'type'       => 'int'
        ));

        $bouh = $metadata->createEntity();
        $this->calling($bouh)->setOld = function ($old) {
            $this->old = $old;
        };

        $this
            ->if($metadata->setEntityProperty($bouh, 'boo_old', '32'))
            ->integer($bouh->old)
                ->isIdenticalTo(32);
    }

    public function testSetEntityPropertyShouldCastToDouble()
    {
        $services = new \CCMBenchmark\Ting\Services();
        $metadata = new \CCMBenchmark\Ting\Repository\Metadata($services->get('SerializerFactory'));
        $metadata->setEntity('mock\repository\Bouh');
        $metadata->addField(array(
            'fieldName'  => 'old',
            'columnName' => 'boo_old',
            'type'       => 'double'
        ));

        $bouh = $metadata->createEntity();
        $this->calling($bouh)->setOld = function ($old) {
            $this->old = $old;
        };

        $this
            ->if($metadata->setEntityProperty($bouh, 'boo_old', '32.3'))
            ->float($bouh->old)
                ->isIdenticalTo(32.3);
    }

    public function testSetEntityPropertyShouldCastToBool()
    {
        $services = new \CCMBenchmark\Ting\Services();
        $metadata = new \CCMBenchmark\Ting\Repository\Metadata($services->get('SerializerFactory'));
        $metadata->setEntity('mock\repository\Bouh');
        $metadata->addField(array(
            'fieldName'  => 'swag',
            'columnName' => 'boo_swag',
            'type'       => 'bool'
        ));

        $bouh = $metadata->createEntity();
        $this->calling($bouh)->setSwag = function ($isSwag) {
            $this->swag = $isSwag;
        };

        $this
            ->if($metadata->setEntityProperty($bouh, 'boo_swag', '1'))
            ->variable($bouh->swag)
                ->isIdenticalTo(true);
    }

    public function testSetEntityPropertyShouldUnserializeData()
    {
        $services = new \CCMBenchmark\Ting\Services();
        $metadata = new \CCMBenchmark\Ting\Repository\Metadata($services->get('SerializerFactory'));
        $metadata->setEntity('mock\repository\Bouh');
        $metadata->addField(array(
            'fieldName'  => 'roles',
            'columnName' => 'boo_roles',
            'type'       => 'string',
            'serializer' => 'CCMBenchmark\Ting\Serializer\Json'
        ));

        $bouh = $metadata->createEntity();
        $this->calling($bouh)->setRoles = function ($roles) {
            $this->roles = $roles;
        };

        $this
            ->if($metadata->setEntityProperty($bouh, 'boo_roles', json_encode(['Bouh', 'Sylvain'])))
            ->array($bouh->roles)
                ->isIdenticalTo(['Bouh', 'Sylvain']);
    }

    public function testSetEntityPropertyShouldUnserializeDataWithOptions()
    {
        $services = new \CCMBenchmark\Ting\Services();
        $metadata = new \CCMBenchmark\Ting\Repository\Metadata($services->get('SerializerFactory'));
        $metadata->setEntity('mock\repository\Bouh');
        $metadata->addField(array(
            'fieldName'  => 'roles',
            'columnName' => 'boo_roles',
            'type'       => 'string',
            'serializer' => 'CCMBenchmark\Ting\Serializer\Json',
            'serializer_options' => [
                'unserialize' => ['assoc' => true]
            ]
        ));

        $bouh = $metadata->createEntity();
        $this->calling($bouh)->setRoles = function ($roles) {
            $this->roles = $roles;
        };

        $this
            ->if($metadata->setEntityProperty($bouh, 'boo_roles', json_encode(['Bouh', 'Sylvain'])))
            ->array($bouh->roles)
            ->isIdenticalTo(['Bouh', 'Sylvain']);
    }

    public function testSetEntityPropertyForAutoIncrement()
    {
        $services = new \CCMBenchmark\Ting\Services();
        $metadata = new \CCMBenchmark\Ting\Repository\Metadata($services->get('SerializerFactory'));
        $metadata->setEntity('mock\repository\Bouh');
        $metadata->addField(array(
            'primary'       => true,
            'autoincrement' => true,
            'fieldName'     => 'id',
            'columnName'    => 'boo_id',
            'type'          => 'int'
        ));

        $driver = new \mock\CCMBenchmark\Ting\Driver\Mysqli\Driver();
        $this->calling($driver)->getInsertId = 321;

        $bouh = $metadata->createEntity();
        $this->calling($bouh)->setId = function ($id) {
            $this->id = $id;
        };

        $this
            ->if($metadata->setEntityPropertyForAutoIncrement($bouh, $driver))
            ->integer($bouh->id)
                ->isIdenticalTo(321);
    }

    public function testSetEntityPropertyForAutoIncrementWithoutAutoIncrementColumnShouldReturnFalse()
    {
        $services = new \CCMBenchmark\Ting\Services();
        $metadata = new \CCMBenchmark\Ting\Repository\Metadata($services->get('SerializerFactory'));
        $metadata->setEntity('mock\repository\Bouh');
        $metadata->addField(array(
            'primary'    => true,
            'fieldName'  => 'id',
            'columnName' => 'boo_id',
            'type'       => 'int'
        ));

        $driver = new \mock\CCMBenchmark\Ting\Driver\Mysqli\Driver();
        $this->calling($driver)->getInsertId = 321;

        $bouh = $metadata->createEntity();
        $this->calling($bouh)->setId = function ($id) {
            $this->id = $id;
        };

        $this
            ->boolean($metadata->setEntityPropertyForAutoIncrement($bouh, $driver))
                ->isFalse();
    }

    public function testGetByPrimariesShouldRaiseExceptionIfIncorrectPrimaries()
    {
        $mockConnectionPool = new \mock\CCMBenchmark\Ting\ConnectionPool();
        $this->calling($mockConnectionPool)->slave = new \tests\fixtures\FakeDriver\Driver();
        $mockConnection = new \mock\CCMBenchmark\Ting\Connection($mockConnectionPool, 'main', 'db');

        $services = new \CCMBenchmark\Ting\Services();
        $this
            ->if($metadata = new \CCMBenchmark\Ting\Repository\Metadata($services->get('SerializerFactory')))
            ->and($metadata->setEntity('mock\repository\Bouh'))
            ->and(
                $metadata->addField([
                    'primary'    => true,
                    'fieldName'  => 'id',
                    'columnName' => 'boo_id',
                    'type'       => 'int'
                ])
            )
            ->and(
                $metadata->addField([
                    'primary'    => true,
                    'fieldName'  => 'secondId',
                    'columnName' => 'wonderful_id',
                    'type'       => 'int'
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
            ->if($metadata = new \CCMBenchmark\Ting\Repository\Metadata($services->get('SerializerFactory')))
            ->and($metadata->setEntity('mock\repository\Bouh'))
            ->and(
                $metadata->addField([
                    'primary'    => true,
                    'fieldName'  => 'id',
                    'columnName' => 'boo_id',
                    'type'       => 'int'
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

    public function testGetOneByCriteriaShouldReturnAQuery()
    {
        $mockConnectionPool = new \mock\CCMBenchmark\Ting\ConnectionPool();
        $this->calling($mockConnectionPool)->slave = new \tests\fixtures\FakeDriver\Driver();
        $mockConnection = new \mock\CCMBenchmark\Ting\Connection($mockConnectionPool, 'main', 'db');

        $services = new \CCMBenchmark\Ting\Services();
        $this
            ->if($metadata = new \CCMBenchmark\Ting\Repository\Metadata($services->get('SerializerFactory')))
            ->and($metadata->setEntity('mock\repository\Bouh'))
            ->and(
                $metadata->addField([
                    'primary'    => true,
                    'fieldName'  => 'id',
                    'columnName' => 'boo_id',
                    'type'       => 'int'
                ])
                &&
                $metadata->addField([
                    'fieldName'  => 'name',
                    'columnName' => 'boo_name',
                    'type'       => 'string'
                ])
            )
            ->object(
                $metadata->getOneByCriteria(
                    $mockConnection,
                    $services->get('QueryFactory'),
                    $services->get('CollectionFactory'),
                    ['name' => 'Xavier']
                )
            )
                ->isInstanceOf('CCMBenchmark\Ting\Query\Query')
        ;
    }

    public function testGetOneByCriteriaShouldRaiseExceptionOnUnknownField()
    {
        $mockConnectionPool = new \mock\CCMBenchmark\Ting\ConnectionPool();
        $this->calling($mockConnectionPool)->slave = new \tests\fixtures\FakeDriver\Driver();
        $mockConnection = new \mock\CCMBenchmark\Ting\Connection($mockConnectionPool, 'main', 'db');

        $services = new \CCMBenchmark\Ting\Services();
        $this
            ->if($metadata = new \CCMBenchmark\Ting\Repository\Metadata($services->get('SerializerFactory')))
            ->and($metadata->setEntity('mock\repository\Bouh'))
            ->and(
                $metadata->addField([
                    'primary'    => true,
                    'fieldName'  => 'id',
                    'columnName' => 'boo_id',
                    'type'       => 'int'
                ])
                &&
                $metadata->addField([
                    'fieldName'  => 'name',
                    'columnName' => 'boo_name',
                    'type'       => 'string'
                ])
            )
            ->exception(
                function () use ($metadata, $mockConnection, $services) {
                    $metadata->getOneByCriteria(
                        $mockConnection,
                        $services->get('QueryFactory'),
                        $services->get('CollectionFactory'),
                        ['weirdColumnName' => 'Xavier']
                    );
                }
            )
                ->isInstanceOf('CCMBenchmark\Ting\Exception')
        ;
    }

    public function testGetAllShouldReturnAQuery()
    {
        $mockConnectionPool = new \mock\CCMBenchmark\Ting\ConnectionPool();
        $this->calling($mockConnectionPool)->slave = new \tests\fixtures\FakeDriver\Driver();
        $mockConnection = new \mock\CCMBenchmark\Ting\Connection($mockConnectionPool, 'main', 'db');

        $services = new \CCMBenchmark\Ting\Services();
        $this
            ->if($metadata = new \CCMBenchmark\Ting\Repository\Metadata($services->get('SerializerFactory')))
            ->and($metadata->setEntity('mock\repository\Bouh'))
            ->and(
                $metadata->addField([
                    'primary'    => true,
                    'fieldName'  => 'id',
                    'columnName' => 'boo_id',
                    'type'       => 'int'
                ])
            )
            ->object(
                $metadata->getAll(
                    $mockConnection,
                    $services->get('QueryFactory'),
                    $services->get('CollectionFactory')
                )
            )
            ->isInstanceOf('CCMBenchmark\Ting\Query\Query')
        ;
    }

    public function testGetByCriteriaShouldReturnAQuery()
    {
        $mockConnectionPool = new \mock\CCMBenchmark\Ting\ConnectionPool();
        $this->calling($mockConnectionPool)->slave = new \tests\fixtures\FakeDriver\Driver();
        $mockConnection = new \mock\CCMBenchmark\Ting\Connection($mockConnectionPool, 'main', 'db');

        $services = new \CCMBenchmark\Ting\Services();
        $this
            ->if($metadata = new \CCMBenchmark\Ting\Repository\Metadata($services->get('SerializerFactory')))
            ->and($metadata->setEntity('mock\repository\Bouh'))
            ->and(
                $metadata->addField([
                    'primary'    => true,
                    'fieldName'  => 'id',
                    'columnName' => 'boo_id',
                    'type'       => 'int'
                ])
                &&
                $metadata->addField([
                    'fieldName'  => 'name',
                    'columnName' => 'boo_name',
                    'type'       => 'string'
                ])
            )
            ->object(
                $metadata->getByCriteria(
                    ['name' => 'Xavier'],
                    $mockConnection,
                    $services->get('QueryFactory'),
                    $services->get('CollectionFactory')
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

        $mockDriver = new \mock\tests\fixtures\FakeDriver\Driver();
        $mockStatement = new \mock\CCMBenchmark\Ting\Driver\StatementInterface(new \stdClass(), [], '', '');
        $this->calling($mockDriver)->prepare = $mockStatement;
        $this->calling($mockConnectionPool)->master = $mockDriver;

        $entity = new Bouh();
        $entity->setName('Xavier');

        $services = new \CCMBenchmark\Ting\Services();
        $this
            ->if($metadata = new \CCMBenchmark\Ting\Repository\Metadata($services->get('SerializerFactory')))
            ->and($metadata->setEntity('mock\repository\Bouh'))
            ->and($metadata->setTable('bouh'))
            ->and(
                $metadata->addField([
                    'primary'    => true,
                    'fieldName'  => 'id',
                    'columnName' => 'boo_id',
                    'type'       => 'int'
                ])
            )
            ->and(
                $metadata->addField([
                    'fieldName'  => 'name',
                    'columnName' => 'boo_name',
                    'type'       => 'int'
                ])
            )
            ->then($query = $metadata->generateQueryForInsert($mockConnection, $services->get('QueryFactory'), $entity))
            ->object($query)
                ->isInstanceOf('CCMBenchmark\Ting\Query\PreparedQuery')
                ->if($query->execute())
                    ->mock($mockDriver)
                        ->call('prepare')
                            ->withArguments('INSERT INTO bouh (boo_id, boo_name) VALUES (:boo_id, :boo_name)')
                            ->once()
                    ->mock($mockStatement)
                        ->call('execute')
                            ->withIdenticalArguments(['boo_id' => null, 'boo_name' => 'Xavier'])
                            ->once()

        ;
    }

    public function testGenerateQueryForInsertShouldSerializeArray()
    {
        $services = new \CCMBenchmark\Ting\Services();

        $mockDriver = new \mock\CCMBenchmark\Ting\Driver\Mysqli\Driver();
        $this->calling($mockDriver)->escapeField = function ($field) {
            return $field;
        };

        $mockConnectionPool = new \mock\CCMBenchmark\Ting\ConnectionPool();
        $this->calling($mockConnectionPool)->master = $mockDriver;

        $mockConnection = new \mock\CCMBenchmark\Ting\Connection($mockConnectionPool, 'main', 'db');

        $mockPreparedQuery = new \mock\CCMBenchmark\Ting\Query\PreparedQuery(
            '',
            $mockConnection,
            $services->get('CollectionFactory')
        );
        $this->calling($mockPreparedQuery)->setParams = function ($params) use (&$outerParams) {
            $outerParams = $params;
        };

        $mockQueryFactory = new \mock\CCMBenchmark\Ting\Query\QueryFactory();
        $this->calling($mockQueryFactory)->getPrepared = $mockPreparedQuery;

        $entity = new Bouh();
        $entity->setRoles(['USER', 'ADMIN']);

        $this
            ->if($metadata = new \CCMBenchmark\Ting\Repository\Metadata($services->get('SerializerFactory')))
            ->and($metadata->setEntity('mock\repository\Bouh'))
            ->and(
                $metadata->addField([
                    'primary'    => true,
                    'fieldName'  => 'roles',
                    'columnName' => 'boo_roles',
                    'type'       => 'string',
                    'serializer' => 'CCMBenchmark\Ting\Serializer\Json'
                ])
            )
            ->and($query = $metadata->generateQueryForInsert($mockConnection, $mockQueryFactory, $entity))
            ->string($outerParams['boo_roles'])
                ->isIdenticalTo(json_encode(['USER', 'ADMIN']));
    }

    public function testGenerateQueryForInsertShouldSerializeWithOptions()
    {
        $services = new \CCMBenchmark\Ting\Services();

        $mockDriver = new \mock\CCMBenchmark\Ting\Driver\Mysqli\Driver();
        $this->calling($mockDriver)->escapeField = function ($field) {
            return $field;
        };

        $mockConnectionPool = new \mock\CCMBenchmark\Ting\ConnectionPool();
        $this->calling($mockConnectionPool)->master = $mockDriver;

        $mockConnection = new \mock\CCMBenchmark\Ting\Connection($mockConnectionPool, 'main', 'db');

        $mockPreparedQuery = new \mock\CCMBenchmark\Ting\Query\PreparedQuery(
            '',
            $mockConnection,
            $services->get('CollectionFactory')
        );
        $this->calling($mockPreparedQuery)->setParams = function ($params) use (&$outerParams) {
            $outerParams = $params;
        };

        $mockQueryFactory = new \mock\CCMBenchmark\Ting\Query\QueryFactory();
        $this->calling($mockQueryFactory)->getPrepared = $mockPreparedQuery;

        $entity = new Bouh();
        $entity->setRoles(['USER', '"BOUH"']);

        $this
            ->if($metadata = new \CCMBenchmark\Ting\Repository\Metadata($services->get('SerializerFactory')))
            ->and($metadata->setEntity('mock\repository\Bouh'))
            ->and(
                $metadata->addField([
                    'primary'    => true,
                    'fieldName'  => 'roles',
                    'columnName' => 'boo_roles',
                    'type'       => 'string',
                    'serializer' => '\CCMBenchmark\Ting\Serializer\Json',
                    'serializer_options' => [
                        'serialize'   => ['options' => JSON_HEX_QUOT]
                    ]
                ])
            )
            ->and($query = $metadata->generateQueryForInsert($mockConnection, $mockQueryFactory, $entity))
            ->string($outerParams['boo_roles'])
            ->isIdenticalTo(json_encode(['USER', '"BOUH"'], JSON_HEX_QUOT));
    }

    public function testGenerateQueryForInsertShouldNotUseAutoIncrementColumn()
    {
        $services = new \CCMBenchmark\Ting\Services();

        $mockDriver = new \mock\CCMBenchmark\Ting\Driver\Mysqli\Driver();
        $this->calling($mockDriver)->escapeField = function ($field) {
            return $field;
        };

        $mockConnectionPool = new \mock\CCMBenchmark\Ting\ConnectionPool();
        $this->calling($mockConnectionPool)->master = $mockDriver;

        $mockConnection = new \mock\CCMBenchmark\Ting\Connection($mockConnectionPool, 'main', 'db');

        $mockPreparedQuery = new \mock\CCMBenchmark\Ting\Query\PreparedQuery(
            '',
            $mockConnection,
            $services->get('CollectionFactory')
        );
        $this->calling($mockPreparedQuery)->setParams = function ($params) use (&$outerParams) {
            $outerParams = $params;
        };

        $mockQueryFactory = new \mock\CCMBenchmark\Ting\Query\QueryFactory();
        $this->calling($mockQueryFactory)->getPrepared = function ($sql) use (&$outerSql, $mockPreparedQuery) {
            $outerSql = $sql;
            return $mockPreparedQuery;
        };

        $entity = new Bouh();
        $entity->setRoles(['USER', 'ADMIN']);

        $this
            ->if($metadata = new \CCMBenchmark\Ting\Repository\Metadata($services->get('SerializerFactory')))
            ->and($metadata->setEntity('mock\repository\Bouh'))
            ->and(
                $metadata->addField([
                    'primary'       => true,
                    'autoincrement' => true,
                    'fieldName'     => 'id',
                    'columnName'    => 'boo_id',
                    'type'          => 'int',
                    'serializer'    => 'CCMBenchmark\Ting\Serializer\Json'
                ])
            )
            ->and(
                $metadata->addField([
                    'fieldName'  => 'roles',
                    'columnName' => 'boo_roles',
                    'type'       => 'string',
                    'serializer' => 'CCMBenchmark\Ting\Serializer\Json'
                ])
            )
            ->and($query = $metadata->generateQueryForInsert($mockConnection, $mockQueryFactory, $entity))
            ->string($outerSql)
            ->isIdenticalTo('INSERT INTO  (boo_roles) VALUES (:boo_roles)');
    }

    public function testGenerateQueryForUpdateShouldReturnAPreparedQuery()
    {
        $mockConnectionPool = new \mock\CCMBenchmark\Ting\ConnectionPool();
        $mockDriver = new \mock\tests\fixtures\FakeDriver\Driver();
        $mockStatement = new \mock\CCMBenchmark\Ting\Driver\StatementInterface(new \stdClass(), [], '', '');
        $this->calling($mockDriver)->prepare = $mockStatement;
        $this->calling($mockConnectionPool)->master = $mockDriver;
        $mockConnection = new \mock\CCMBenchmark\Ting\Connection($mockConnectionPool, 'main', 'db');

        $entity = new Bouh();
        $entity->setId(20);
        $entity->setName('Xavier');

        $services = new \CCMBenchmark\Ting\Services();
        $this
            ->if($metadata = new \CCMBenchmark\Ting\Repository\Metadata($services->get('SerializerFactory')))
            ->and($metadata->setEntity('mock\repository\Bouh'))
            ->and($metadata->setTable('bouh'))
            ->and(
                $metadata->addField([
                    'primary'    => true,
                    'fieldName'  => 'id',
                    'columnName' => 'boo_id',
                    'type'       => 'int'
                ])
            )
            ->and(
                $metadata->addField([
                    'primary'    => true,
                    'fieldName'  => 'name',
                    'columnName' => 'firstname',
                    'type'       => 'int'
                ])
            )
            ->then(
                $query = $metadata->generateQueryForUpdate(
                    $mockConnection,
                    $services->get('QueryFactory'),
                    $entity,
                    ['name' => 'Sylvain']
                )
            )
            ->object($query)
                ->isInstanceOf('CCMBenchmark\Ting\Query\PreparedQuery')
                    ->if($query->execute())
                        ->mock($mockDriver)
                            ->call('prepare')
                                ->withArguments('UPDATE bouh SET firstname = :firstname WHERE boo_id = :#boo_id AND firstname = :#firstname')
                                    ->once()
                        ->mock($mockStatement)
                            ->call('execute')
                                ->withArguments(['#boo_id' => 20, '#firstname' => 'Sylvain', 'firstname' => 'Xavier'])
                                    ->once()
        ;
    }

    public function testGenerateQueryForDeleteShouldReturnAPreparedQuery()
    {
        $mockConnectionPool = new \mock\CCMBenchmark\Ting\ConnectionPool();
        $mockDriver = new \mock\tests\fixtures\FakeDriver\Driver();
        $mockStatement = new \mock\CCMBenchmark\Ting\Driver\StatementInterface(new \stdClass(), [], '', '');
        $this->calling($mockDriver)->prepare = $mockStatement;
        $this->calling($mockConnectionPool)->master = $mockDriver;
        $mockConnection = new \mock\CCMBenchmark\Ting\Connection($mockConnectionPool, 'main', 'db');

        $entity = new Bouh();
        $entity->setName('Xavier');

        $services = new \CCMBenchmark\Ting\Services();
        $this
            ->if($metadata = new \CCMBenchmark\Ting\Repository\Metadata($services->get('SerializerFactory')))
            ->and($metadata->setEntity('mock\repository\Bouh'))
            ->and(
                $metadata->addField([
                    'primary'    => true,
                    'fieldName'  => 'id',
                    'columnName' => 'boo_id',
                    'type'       => 'int'
                ])
            )
            ->and(
                $metadata->setTable('bouh')
            )
            ->then($query = $metadata->generateQueryForDelete($mockConnection, $services->get('QueryFactory'), ['id' => 1], $entity))
            ->object(
                $query
            )
                ->isInstanceOf('CCMBenchmark\Ting\Query\PreparedQuery')
                    ->if($query->execute())
                        ->mock($mockDriver)
                            ->call('prepare')
                                ->withArguments('DELETE FROM bouh WHERE boo_id = :#boo_id')
                                    ->once()
                        ->mock($mockStatement)
                            ->call('execute')
                                ->withIdenticalArguments(['#boo_id' => 1])
                                    ->once()
        ;
    }

    public function testSetEntityPropertyWithDefinedSetter()
    {
        $services = new \CCMBenchmark\Ting\Services();
        $metadata = new \CCMBenchmark\Ting\Repository\Metadata($services->get('SerializerFactory'));
        $metadata->setEntity('mock\repository\Bouh');
        $metadata->addField(array(
            'fieldName'  => 'name',
            'columnName' => 'boo_name',
            'type'       => 'string',
            'setter'     => 'nameIs'
        ));

        $bouh = $metadata->createEntity();
        $this->calling($bouh)->nameIs = function ($name) {
            $this->name = $name;
        };

        $this
            ->if($metadata->setEntityProperty($bouh, 'boo_name', 'Sylvain'))
            ->string($bouh->name)
                ->isIdenticalTo('Sylvain')
            ->mock($bouh)
                ->call('nameIs')
                    ->once();
    }

    public function testCustomGetterReturnGoodValue()
    {
        $services = new \CCMBenchmark\Ting\Services();

        $mockDriver = new \mock\CCMBenchmark\Ting\Driver\Mysqli\Driver();
        $this->calling($mockDriver)->escapeField = function ($field) {
            return $field;
        };

        $mockConnectionPool = new \mock\CCMBenchmark\Ting\ConnectionPool();
        $this->calling($mockConnectionPool)->master = $mockDriver;

        $mockConnection = new \mock\CCMBenchmark\Ting\Connection($mockConnectionPool, 'main', 'db');

        $mockPreparedQuery = new \mock\CCMBenchmark\Ting\Query\PreparedQuery(
            '',
            $mockConnection,
            $services->get('CollectionFactory')
        );
        $this->calling($mockPreparedQuery)->setParams = function ($params) use (&$outerParams) {
            $outerParams = $params;
        };

        $mockQueryFactory = new \mock\CCMBenchmark\Ting\Query\QueryFactory();
        $this->calling($mockQueryFactory)->getPrepared = $mockPreparedQuery;

        $entity = new BouhCustomGetter();
        $entity->setName('Nicolas');

        $this
            ->if($metadata = new \CCMBenchmark\Ting\Repository\Metadata($services->get('SerializerFactory')))
                ->and($metadata->setEntity('mock\repository\BouhCustomGetter'))
                ->and(
                    $metadata->addField([
                        'primary'    => true,
                        'fieldName'  => 'name',
                        'columnName' => 'boo_name',
                        'type'       => 'string',
                        'getter'     => 'nameIs'
                    ])
                )
                ->and($query = $metadata->generateQueryForInsert($mockConnection, $mockQueryFactory, $entity))
                    ->string($outerParams['boo_name'])
                        ->isIdenticalTo('Nicolas');

    }

    public function testGetGetterAndGetSetterWithDefaultValue()
    {
        $services = new \CCMBenchmark\Ting\Services();
        $metadata = new \CCMBenchmark\Ting\Repository\Metadata($services->get('SerializerFactory'));
        $metadata->setEntity('mock\repository\Bouh');
        $metadata->addField(array(
            'fieldName'  => 'name',
            'columnName' => 'boo_name',
            'type'       => 'string',
        ));

        $this
            ->string($metadata->getGetter('name'))
                ->isIdenticalTo('getname')
            ->string($metadata->getSetter('name'))
                ->isIdenticalTo('setname');
    }

    public function testGetGetterAndGetSetterWithCustomValues()
    {
        $services = new \CCMBenchmark\Ting\Services();
        $metadata = new \CCMBenchmark\Ting\Repository\Metadata($services->get('SerializerFactory'));
        $metadata->setEntity('mock\repository\Bouh');
        $getter = uniqid('getter');
        $setter = uniqid('setter');
        $metadata->addField(array(
            'fieldName'  => 'name',
            'columnName' => 'boo_name',
            'type'       => 'string',
            'getter'     => $getter,
            'setter'     => $setter
        ));

        $this
            ->string($metadata->getGetter('name'))
                ->isIdenticalTo($getter)
            ->string($metadata->getSetter('name'))
                ->isIdenticalTo($setter);
    }

}
