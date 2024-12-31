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

use CCMBenchmark\Ting\Driver\Mysqli\Result;
use CCMBenchmark\Ting\MetadataRepository;
use CCMBenchmark\Ting\Serializer\DateTime;
use CCMBenchmark\Ting\Serializer\Json;
use CCMBenchmark\Ting\UnitOfWork;
use atoum;
use tests\fixtures\model\City;
use tests\fixtures\model\CityRepository;
use tests\fixtures\model\PrimaryOnMultiField;

class Hydrator extends atoum
{
    public function testHydrate()
    {
        $services = new \CCMBenchmark\Ting\Services();
        $metadata = new \CCMBenchmark\Ting\Repository\Metadata($services->get('SerializerFactory'));
        $metadata->setConnectionName('connectionName');
        $metadata->setDatabase('database');
        $metadata->setEntity('tests\fixtures\model\Bouh');
        $metadata->setTable('T_BOUH_BOO');

        $metadata->addField([
            'fieldName'  => 'name',
            'columnName' => 'boo_name',
            'type'       => 'string'
        ]);

        $metadata->addField([
            'fieldName'  => 'firstname',
            'columnName' => 'boo_firstname',
            'type'       => 'string'
        ]);

        $services->get('MetadataRepository')->addMetadata('tests\fixtures\model\BouhRepository', $metadata);

        $mockMysqliResult = new \mock\tests\fixtures\FakeDriver\MysqliResult([['Sylvain', 'Robez-Masson']]);
        $this->calling($mockMysqliResult)->fetch_fields = function () {
            $fields = [];
            $stdClass = new \stdClass();
            $stdClass->name     = 'fname';
            $stdClass->orgname  = 'boo_firstname';
            $stdClass->table    = 'bouh';
            $stdClass->orgtable = 'T_BOUH_BOO';
            $stdClass->type     = MYSQLI_TYPE_VAR_STRING;
            $fields[] = $stdClass;

            $stdClass = new \stdClass();
            $stdClass->name     = 'name';
            $stdClass->orgname  = 'boo_name';
            $stdClass->table    = 'bouh';
            $stdClass->orgtable = 'T_BOUH_BOO';
            $stdClass->type     = MYSQLI_TYPE_VAR_STRING;
            $fields[] = $stdClass;
            return $fields;
        };

        $result = new Result();
        $result->setResult($mockMysqliResult);
        $result->setConnectionName('connectionName');
        $result->setDatabase('database');

        $this
            ->if($hydrator = new \CCMBenchmark\Ting\Repository\Hydrator())
            ->and($hydrator->setMetadataRepository($services->get('MetadataRepository')))
            ->and($hydrator->setUnitOfWork($services->get('UnitOfWork')))
            ->then($iterator = $hydrator->setResult($result)->getIterator())
            ->then($data = $iterator->current())
            ->string($data['bouh']->getName())
                ->isIdenticalTo('Robez-Masson')
            ->string($data['bouh']->getFirstname())
                ->isIdenticalTo('Sylvain');
    }

    public function testHydrateForEntityWithouNotifyPropertyInterfaceShouldWork()
    {
        $services = new \CCMBenchmark\Ting\Services();
        $metadata = new \CCMBenchmark\Ting\Repository\Metadata($services->get('SerializerFactory'));
        $metadata->setConnectionName('connectionName');
        $metadata->setDatabase('database');
        $metadata->setEntity('tests\fixtures\model\BouhReadOnly');
        $metadata->setTable('T_BOUH_BOO');

        $metadata->addField([
            'fieldName'  => 'name',
            'columnName' => 'boo_name',
            'type'       => 'string'
        ]);

        $metadata->addField([
            'fieldName'  => 'firstname',
            'columnName' => 'boo_firstname',
            'type'       => 'string'
        ]);

        $services->get('MetadataRepository')->addMetadata('tests\fixtures\model\BouhReadOnlyRepository', $metadata);

        $mockMysqliResult = new \mock\tests\fixtures\FakeDriver\MysqliResult([['Sylvain', 'Robez-Masson']]);
        $this->calling($mockMysqliResult)->fetch_fields = function () {
            $fields = [];
            $stdClass = new \stdClass();
            $stdClass->name     = 'fname';
            $stdClass->orgname  = 'boo_firstname';
            $stdClass->table    = 'bouh';
            $stdClass->orgtable = 'T_BOUH_BOO';
            $stdClass->type     = MYSQLI_TYPE_VAR_STRING;
            $fields[] = $stdClass;

            $stdClass = new \stdClass();
            $stdClass->name     = 'name';
            $stdClass->orgname  = 'boo_name';
            $stdClass->table    = 'bouh';
            $stdClass->orgtable = 'T_BOUH_BOO';
            $stdClass->type     = MYSQLI_TYPE_VAR_STRING;
            $fields[] = $stdClass;
            return $fields;
        };

        $result = new Result();
        $result->setResult($mockMysqliResult);
        $result->setConnectionName('connectionName');
        $result->setDatabase('database');

        $this
            ->if($hydrator = new \CCMBenchmark\Ting\Repository\Hydrator())
            ->and($hydrator->setMetadataRepository($services->get('MetadataRepository')))
            ->and($hydrator->setUnitOfWork($services->get('UnitOfWork')))
            ->then($iterator = $hydrator->setResult($result)->getIterator())
            ->then($data = $iterator->current())
            ->string($data['bouh']->getName())
                ->isIdenticalTo('Robez-Masson')
            ->string($data['bouh']->getFirstname())
                ->isIdenticalTo('Sylvain');
    }

    public function testHydrateWithSchema()
    {
        $services = new \CCMBenchmark\Ting\Services();

        $services->get('MetadataRepository')->addMetadata(
            'tests\fixtures\model\BouhRepository',
            \tests\fixtures\model\BouhRepository::initMetadata($services->get('SerializerFactory'))
        );

        $services->get('MetadataRepository')->addMetadata(
            'tests\fixtures\model\BouhMySchemaRepository',
            \tests\fixtures\model\BouhMySchemaRepository::initMetadata($services->get('SerializerFactory'))
        );

        $result = new \mock\CCMBenchmark\Ting\Driver\Pgsql\Result();
        $this->calling($result)->rewind = true;
        $this->calling($result)->valid = true;
        $this->calling($result)->current = [
            [
                'name' => 'fname',
                'orgName' => 'boo_firstname',
                'schema' => 'mySchema',
                'table' => 'bouh',
                'orgTable' => 'T_BOUH_BOO',
                'value' => 'Sylvain'
            ],
            [
                'name' => 'name',
                'orgName' => 'boo_name',
                'schema' => 'mySchema',
                'table' => 'bouh',
                'orgTable' => 'T_BOUH_BOO',
                'value' => 'Robez-Masson'
            ]
        ];

        $result->setResult(new \CCMBenchmark\Ting\Driver\Pgsql\Result());
        $result->setConnectionName('main');
        $result->setDatabase('bouh_world');

        $this
            ->if($hydrator = new \CCMBenchmark\Ting\Repository\Hydrator())
            ->and($hydrator->setMetadataRepository($services->get('MetadataRepository')))
            ->and($hydrator->setUnitOfWork($services->get('UnitOfWork')))
            ->then($iterator = $hydrator->setResult($result)->getIterator())
            ->then($data = $iterator->current())
            ->string($data['bouh']->getName())
                ->isIdenticalTo('MySchemaRobez-Masson')
            ->string($data['bouh']->getFirstname())
                ->isIdenticalTo('MySchemaSylvain');
    }

    public function testHydrateWithAllNullValueShouldReturnNull()
    {
        $services = new \CCMBenchmark\Ting\Services();
        $metadata = new \CCMBenchmark\Ting\Repository\Metadata($services->get('SerializerFactory'));
        $metadata->setConnectionName('connectionName');
        $metadata->setDatabase('database');
        $metadata->setEntity('tests\fixtures\model\Bouh');
        $metadata->setTable('T_BOUH_BOO');

        $metadata->addField([
            'fieldName'  => 'name',
            'columnName' => 'boo_name',
            'type'       => 'string'
        ]);

        $metadata->addField([
            'fieldName'  => 'firstname',
            'columnName' => 'boo_firstname',
            'type'       => 'string'
        ]);

        $services->get('MetadataRepository')->addMetadata('tests\fixtures\model\BouhRepository', $metadata);

        $mockMysqliResult = new \mock\tests\fixtures\FakeDriver\MysqliResult([[null, null]]);
        $this->calling($mockMysqliResult)->fetch_fields = function () {
            $fields = [];
            $stdClass = new \stdClass();
            $stdClass->name     = 'fname';
            $stdClass->orgname  = 'boo_firstname';
            $stdClass->table    = 'bouh';
            $stdClass->orgtable = 'T_BOUH_BOO';
            $stdClass->type     = MYSQLI_TYPE_VAR_STRING;
            $fields[] = $stdClass;

            $stdClass = new \stdClass();
            $stdClass->name     = 'name';
            $stdClass->orgname  = 'boo_name';
            $stdClass->table    = 'bouh';
            $stdClass->orgtable = 'T_BOUH_BOO';
            $stdClass->type     = MYSQLI_TYPE_VAR_STRING;
            $fields[] = $stdClass;
            return $fields;
        };

        $result = new Result();
        $result->setResult($mockMysqliResult);
        $result->setConnectionName('connectionName');
        $result->setDatabase('database');

        $this
            ->if($hydrator = new \CCMBenchmark\Ting\Repository\Hydrator())
            ->and($hydrator->setMetadataRepository($services->get('MetadataRepository')))
            ->and($hydrator->setUnitOfWork($services->get('UnitOfWork')))
            ->then($iterator = $hydrator->setResult($result)->getIterator())
            ->then($data = $iterator->current())
            ->variable($data['bouh'])
                ->isNull();
    }

    public function testHydrateWithSomeNullValueShouldNotReturnNull()
    {
        $services = new \CCMBenchmark\Ting\Services();
        $metadata = new \CCMBenchmark\Ting\Repository\Metadata($services->get('SerializerFactory'));
        $metadata->setConnectionName('connectionName');
        $metadata->setDatabase('database');
        $metadata->setEntity('tests\fixtures\model\Bouh');
        $metadata->setTable('T_BOUH_BOO');

        $metadata->addField([
            'fieldName'  => 'name',
            'columnName' => 'boo_name',
            'type'       => 'string'
        ]);

        $metadata->addField([
            'fieldName'  => 'firstname',
            'columnName' => 'boo_firstname',
            'type'       => 'string'
        ]);

        $services->get('MetadataRepository')->addMetadata('tests\fixtures\model\BouhRepository', $metadata);

        $mockMysqliResult = new \mock\tests\fixtures\FakeDriver\MysqliResult([[null, 'Robez-Masson']]);
        $this->calling($mockMysqliResult)->fetch_fields = function () {
            $fields = [];
            $stdClass = new \stdClass();
            $stdClass->name     = 'fname';
            $stdClass->orgname  = 'boo_firstname';
            $stdClass->table    = 'bouh';
            $stdClass->orgtable = 'T_BOUH_BOO';
            $stdClass->type     = MYSQLI_TYPE_VAR_STRING;
            $fields[] = $stdClass;

            $stdClass = new \stdClass();
            $stdClass->name     = 'name';
            $stdClass->orgname  = 'boo_name';
            $stdClass->table    = 'bouh';
            $stdClass->orgtable = 'T_BOUH_BOO';
            $stdClass->type     = MYSQLI_TYPE_VAR_STRING;
            $fields[] = $stdClass;
            return $fields;
        };

        $result = new Result();
        $result->setResult($mockMysqliResult);
        $result->setConnectionName('connectionName');
        $result->setDatabase('database');

        $this
            ->if($hydrator = new \CCMBenchmark\Ting\Repository\Hydrator())
            ->and($hydrator->setMetadataRepository($services->get('MetadataRepository')))
            ->and($hydrator->setUnitOfWork($services->get('UnitOfWork')))
            ->then($iterator = $hydrator->setResult($result)->getIterator())
            ->then($data = $iterator->current())
            ->string($data['bouh']->getName())
                ->isIdenticalTo('Robez-Masson');
    }

    public function testHydrateShouldHydrateUnknownColumnIntoKey0()
    {
        $services = new \CCMBenchmark\Ting\Services();
        $metadata = new \CCMBenchmark\Ting\Repository\Metadata($services->get('SerializerFactory'));
        $metadata->setConnectionName('connectionName');
        $metadata->setDatabase('database');
        $metadata->setEntity('tests\fixtures\model\Bouh');
        $metadata->setTable('T_BOUH_BOO');

        $metadata->addField([
            'fieldName'  => 'name',
            'columnName' => 'boo_name',
            'type'       => 'string'
        ]);

        $metadata->addField([
            'fieldName'  => 'firstname',
            'columnName' => 'boo_firstname',
            'type'       => 'string'
        ]);

        $services->get('MetadataRepository')->addMetadata('tests\fixtures\model\BouhRepository', $metadata);

        $mockMysqliResult = new \mock\tests\fixtures\FakeDriver\MysqliResult([['Sylvain', 'Robez-Masson', 'Happy Face']]);
        $this->calling($mockMysqliResult)->fetch_fields = function () {
            $fields = [];
            $stdClass = new \stdClass();
            $stdClass->name     = 'fname';
            $stdClass->orgname  = 'boo_firstname';
            $stdClass->table    = 'bouh';
            $stdClass->orgtable = 'T_BOUH_BOO';
            $stdClass->type     = MYSQLI_TYPE_VAR_STRING;
            $fields[] = $stdClass;

            $stdClass = new \stdClass();
            $stdClass->name     = 'name';
            $stdClass->orgname  = 'boo_name';
            $stdClass->table    = 'bouh';
            $stdClass->orgtable = 'T_BOUH_BOO';
            $stdClass->type     = MYSQLI_TYPE_VAR_STRING;
            $fields[] = $stdClass;

            $stdClass = new \stdClass();
            $stdClass->name     = 'otherColumn';
            $stdClass->orgname  = 'boo_other_column';
            $stdClass->table    = 'bouh';
            $stdClass->orgtable = 'T_BOUH_BOO';
            $stdClass->type     = MYSQLI_TYPE_VAR_STRING;
            $fields[] = $stdClass;
            return $fields;
        };

        $result = new Result();
        $result->setResult($mockMysqliResult);
        $result->setConnectionName('connectionName');
        $result->setDatabase('database');

        $this
            ->if($hydrator = new \CCMBenchmark\Ting\Repository\Hydrator())
            ->and($hydrator->setMetadataRepository($services->get('MetadataRepository')))
            ->and($hydrator->setUnitOfWork($services->get('UnitOfWork')))
            ->then($iterator = $hydrator->setResult($result)->getIterator())
            ->then($data = $iterator->current())
            ->string($data['bouh']->getName())
                ->isIdenticalTo('Robez-Masson')
            ->string($data['bouh']->getFirstname())
                ->isIdenticalTo('Sylvain')
            ->string($data[0]->otherColumn)
                ->isIdenticalTo('Happy Face');
    }


    public function testHydrateShouldHydrateUnknownColumnOfFromReferenceTable()
    {
        $services = new \CCMBenchmark\Ting\Services();
        $services->get('MetadataRepository')
            ->batchLoadMetadata('tests\fixtures\model', __DIR__ . '/../../../fixtures/model/*Repository.php');

        $mockMysqliResult = new \mock\tests\fixtures\FakeDriver\MysqliResult([
            [23, 'LeBron', 'James', 'Cleveland'],
            [23, 'LeBron', 'James', 'Los Angeles']
        ]);

        $this->calling($mockMysqliResult)->fetch_fields = function () {
            $fields = [];

            $stdClass = new \stdClass();
            $stdClass->name     = 'id';
            $stdClass->orgname  = 'boo_id';
            $stdClass->table    = 'bouh';
            $stdClass->orgtable = 'T_BOUH_BOO';
            $stdClass->type     = MYSQLI_TYPE_LONG;
            $fields[] = $stdClass;

            $stdClass = new \stdClass();
            $stdClass->name     = 'firstname';
            $stdClass->orgname  = 'boo_firstname';
            $stdClass->table    = 'bouh';
            $stdClass->orgtable = 'bouh';
            $stdClass->type     = MYSQLI_TYPE_VAR_STRING;
            $fields[] = $stdClass;

            $stdClass = new \stdClass();
            $stdClass->name     = 'name';
            $stdClass->orgname  = 'boo_name';
            $stdClass->table    = 'bouh';
            $stdClass->orgtable = 'T_BOUH_BOO';
            $stdClass->type     = MYSQLI_TYPE_VAR_STRING;
            $fields[] = $stdClass;

            // This column is associated with a mapped table
            // while parsing but not mapped into metadatas
            $stdClass = new \stdClass();
            $stdClass->name     = 'notMappedBouhColumn';
            $stdClass->orgname  = 'bouh_notMappedBouhColumn';
            $stdClass->table    = 'bouh';
            $stdClass->orgtable = 'T_BOUH_BOO';
            $stdClass->type     = MYSQLI_TYPE_VAR_STRING;
            $fields[] = $stdClass;

            return $fields;
        };

        $result = new Result();
        $result->setResult($mockMysqliResult);
        $result->setConnectionName('main');
        $result->setDatabase('bouh_world');

        $this
            ->if($hydrator = new \CCMBenchmark\Ting\Repository\Hydrator())
            ->and($hydrator->setMetadataRepository($services->get('MetadataRepository')))
            ->and($hydrator->setUnitOfWork($services->get('UnitOfWork')))
            ->then($iterator = $hydrator->setResult($result)->getIterator())
            ->then($currentObject = $iterator->current())
            ->then($iterator->next())
            ->then($nextObject = $iterator->current())
            ->boolean(is_array($currentObject) && array_key_exists(0, $currentObject))
            ->isIdenticalTo(true, 'Unmapped column of known table was not hydrated')
            ->string($currentObject[0]->notMappedBouhColumn)
            ->isIdenticalTo('Cleveland')
            ->boolean(is_array($nextObject) && array_key_exists(0, $nextObject))
            ->isIdenticalTo(true, 'Unmapped column of known table was not hydrated')
            ->string($nextObject[0]->notMappedBouhColumn)
            ->isIdenticalTo('Los Angeles')
        ;
    }

    public function testHydrateShouldHydrateIntoKey0()
    {
        $services = new \CCMBenchmark\Ting\Services();

        $mockMysqliResult = new \mock\tests\fixtures\FakeDriver\MysqliResult([['Sylvain', 'Robez-Masson']]);
        $this->calling($mockMysqliResult)->fetch_fields = function () {
            $fields = [];
            $stdClass = new \stdClass();
            $stdClass->name     = 'fname';
            $stdClass->orgname  = 'boo_firstname';
            $stdClass->table    = 'bouh';
            $stdClass->orgtable = 'T_BOUH_BOO';
            $stdClass->type     = MYSQLI_TYPE_VAR_STRING;
            $fields[] = $stdClass;

            $stdClass = new \stdClass();
            $stdClass->name     = 'name';
            $stdClass->orgname  = 'boo_name';
            $stdClass->table    = 'bouh';
            $stdClass->orgtable = 'T_BOUH_BOO';
            $stdClass->type     = MYSQLI_TYPE_VAR_STRING;
            $fields[] = $stdClass;
            return $fields;
        };

        $result = new Result();
        $result->setResult($mockMysqliResult);
        $result->setConnectionName('connectionName');
        $result->setDatabase('database');

        $this
            ->if($hydrator = new \CCMBenchmark\Ting\Repository\Hydrator())
            ->and($hydrator->setMetadataRepository($services->get('MetadataRepository')))
            ->and($hydrator->setUnitOfWork($services->get('UnitOfWork')))
            ->then($iterator = $hydrator->setResult($result)->getIterator())
            ->then($data = $iterator->current())
            ->string($data[0]->name)
                ->isIdenticalTo('Robez-Masson')
            ->string($data[0]->fname)
                ->isIdenticalTo('Sylvain');
    }

    public function testCountShouldReturn3()
    {
        $result = new \mock\CCMBenchmark\Ting\Driver\Mysqli\Result();
        $this->calling($result)->getNumRows = 3;

        $this
            ->if($hydrator = new \CCMBenchmark\Ting\Repository\Hydrator())
            ->then($hydrator->setResult($result))
            ->integer(count($hydrator))
                ->isIdenticalTo(3);
    }

    public function testCountWithoutResultShoulddReturn0()
    {
        $this
            ->if($hydrator = new \CCMBenchmark\Ting\Repository\Hydrator())
            ->integer(count($hydrator))
                ->isIdenticalTo(0);
    }

    public function testHydrateWithMapAliasShouldHydrateToMethodOfObject()
    {
        $services = new \CCMBenchmark\Ting\Services();
        $services->get('MetadataRepository')
            ->batchLoadMetadata('tests\fixtures\model', __DIR__ . '/../../../fixtures/model/*Repository.php');

        $time = time();
        $mockMysqliResult = new \mock\tests\fixtures\FakeDriver\MysqliResult([['Sylvain', 'Robez-Masson', $time]]);
        $this->calling($mockMysqliResult)->fetch_fields = function () {
            $fields = [];
            $stdClass = new \stdClass();
            $stdClass->name     = 'fname';
            $stdClass->orgname  = 'boo_firstname';
            $stdClass->table    = 'bouh';
            $stdClass->orgtable = 'T_BOUH_BOO';
            $stdClass->type     = MYSQLI_TYPE_VAR_STRING;
            $fields[] = $stdClass;

            $stdClass = new \stdClass();
            $stdClass->name     = 'name';
            $stdClass->orgname  = 'boo_name';
            $stdClass->table    = 'bouh';
            $stdClass->orgtable = 'T_BOUH_BOO';
            $stdClass->type     = MYSQLI_TYPE_VAR_STRING;
            $fields[] = $stdClass;

            $stdClass = new \stdClass();
            $stdClass->name     = 'current_time';
            $stdClass->orgname  = '';
            $stdClass->table    = '';
            $stdClass->orgtable = '';
            $stdClass->type     = MYSQLI_TYPE_VAR_STRING;
            $fields[] = $stdClass;
            return $fields;
        };

        $result = new Result();
        $result->setResult($mockMysqliResult);
        $result->setConnectionName('main');
        $result->setDatabase('bouh_world');

        $this
            ->if($hydrator = new \CCMBenchmark\Ting\Repository\Hydrator())
            ->and($hydrator->setMetadataRepository($services->get('MetadataRepository')))
            ->and($hydrator->setUnitOfWork($services->get('UnitOfWork')))
            ->and($hydrator->mapAliasTo('current_time', 'bouh', 'setRetrievedTime'))
            ->then($iterator = $hydrator->setResult($result)->getIterator())
            ->then($data = $iterator->current())
            ->integer($data['bouh']->getRetrievedTime())
                ->isIdenticalTo($time)
            ->array($data)
                ->notHasKey(0);
    }

    public function testHydrateWithMapObjectShouldHydrateToMethodOfObject()
    {
        $services = new \CCMBenchmark\Ting\Services();
        $services->get('MetadataRepository')
            ->batchLoadMetadata('tests\fixtures\model', __DIR__ . '/../../../fixtures/model/*Repository.php');

        $mockMysqliResult = new \mock\tests\fixtures\FakeDriver\MysqliResult([
                ['Sylvain', 'Robez-Masson', 3, 'Palaiseau']
            ]);
        $this->calling($mockMysqliResult)->fetch_fields = function () {
            $fields = [];
            $stdClass = new \stdClass();
            $stdClass->name     = 'fname';
            $stdClass->orgname  = 'boo_firstname';
            $stdClass->table    = 'bouh';
            $stdClass->orgtable = 'T_BOUH_BOO';
            $stdClass->type     = MYSQLI_TYPE_VAR_STRING;
            $fields[] = $stdClass;

            $stdClass = new \stdClass();
            $stdClass->name     = 'name';
            $stdClass->orgname  = 'boo_name';
            $stdClass->table    = 'bouh';
            $stdClass->orgtable = 'T_BOUH_BOO';
            $stdClass->type     = MYSQLI_TYPE_VAR_STRING;
            $fields[] = $stdClass;

            $stdClass = new \stdClass();
            $stdClass->name     = 'cityId';
            $stdClass->orgname  = 'cit_id';
            $stdClass->table    = 'cit';
            $stdClass->orgtable = 'T_CITY_CIT';
            $stdClass->type     = MYSQLI_TYPE_LONG;
            $fields[] = $stdClass;

            $stdClass = new \stdClass();
            $stdClass->name     = 'citname';
            $stdClass->orgname  = 'cit_name';
            $stdClass->table    = 'cit';
            $stdClass->orgtable = 'T_CITY_CIT';
            $stdClass->type     = MYSQLI_TYPE_VAR_STRING;
            $fields[] = $stdClass;
            return $fields;
        };

        $result = new Result();
        $result->setResult($mockMysqliResult);
        $result->setConnectionName('main');
        $result->setDatabase('bouh_world');

        $this
            ->if($hydrator = new \CCMBenchmark\Ting\Repository\Hydrator())
            ->and($hydrator->setMetadataRepository($services->get('MetadataRepository')))
            ->and($hydrator->setUnitOfWork($services->get('UnitOfWork')))
            ->and($hydrator->mapObjectTo('cit', 'bouh', 'setCity'))
            ->then($iterator = $hydrator->setResult($result)->getIterator())
            ->then($data = $iterator->current())
            ->object($city = $data['bouh']->getCity())
            ->integer($city->getId())
                ->isIdenticalTo(3)
            ->string($city->getName())
                ->isIdenticalTo('Palaiseau');
    }

    public function testHydrateWithMapObjectShouldHydrateToMethodOfObjectWithAManagedEntity()
    {
        $services = new \CCMBenchmark\Ting\Services();
        $services->get('MetadataRepository')
            ->batchLoadMetadata('tests\fixtures\model', __DIR__ . '/../../../fixtures/model/*Repository.php');

        $mockMysqliResult = new \mock\tests\fixtures\FakeDriver\MysqliResult([
            ['Sylvain', 'Robez-Masson', 3, 'Palaiseau']
        ]);
        $this->calling($mockMysqliResult)->fetch_fields = function () {
            $fields = [];
            $stdClass = new \stdClass();
            $stdClass->name     = 'fname';
            $stdClass->orgname  = 'boo_firstname';
            $stdClass->table    = 'bouh';
            $stdClass->orgtable = 'T_BOUH_BOO';
            $stdClass->type     = MYSQLI_TYPE_VAR_STRING;
            $fields[] = $stdClass;

            $stdClass = new \stdClass();
            $stdClass->name     = 'name';
            $stdClass->orgname  = 'boo_name';
            $stdClass->table    = 'bouh';
            $stdClass->orgtable = 'T_BOUH_BOO';
            $stdClass->type     = MYSQLI_TYPE_VAR_STRING;
            $fields[] = $stdClass;

            $stdClass = new \stdClass();
            $stdClass->name     = 'cityId';
            $stdClass->orgname  = 'cit_id';
            $stdClass->table    = 'cit';
            $stdClass->orgtable = 'T_CITY_CIT';
            $stdClass->type     = MYSQLI_TYPE_LONG;
            $fields[] = $stdClass;

            $stdClass = new \stdClass();
            $stdClass->name     = 'citname';
            $stdClass->orgname  = 'cit_name';
            $stdClass->table    = 'cit';
            $stdClass->orgtable = 'T_CITY_CIT';
            $stdClass->type     = MYSQLI_TYPE_VAR_STRING;
            $fields[] = $stdClass;
            return $fields;
        };

        $result = new Result();
        $result->setResult($mockMysqliResult);
        $result->setConnectionName('main');
        $result->setDatabase('bouh_world');

        $this
            ->if($hydrator = new \CCMBenchmark\Ting\Repository\Hydrator())
            ->and($hydrator->setMetadataRepository($services->get('MetadataRepository')))
            ->and($hydrator->setUnitOfWork($services->get('UnitOfWork')))
            ->and($hydrator->mapObjectTo('cit', 'bouh', 'setCity'))
            ->then($iterator = $hydrator->setResult($result)->getIterator())
            ->then($data = $iterator->current())
            ->object($city = $data['bouh']->getOriginalCity());
    }

    public function testHydrateWithUnserializeAlias()
    {
        $services = new \CCMBenchmark\Ting\Services();
        $services->get('MetadataRepository')
            ->batchLoadMetadata('tests\fixtures\model', __DIR__ . '/../../../fixtures/model/*Repository.php');

        $mockMysqliResult = new \mock\tests\fixtures\FakeDriver\MysqliResult([
            ['{"name": "Sylvain"}', 'Palaiseau']
        ]);
        $this->calling($mockMysqliResult)->fetch_fields = function () {
            $fields = [];
            $stdClass = new \stdClass();
            $stdClass->name     = 'data';
            $stdClass->orgname  = '';
            $stdClass->table    = '';
            $stdClass->orgtable = '';
            $stdClass->type     = MYSQLI_TYPE_VAR_STRING;
            $fields[] = $stdClass;

            $stdClass = new \stdClass();
            $stdClass->name     = 'citname';
            $stdClass->orgname  = 'cit_name';
            $stdClass->table    = 'cit';
            $stdClass->orgtable = 'T_CITY_CIT';
            $stdClass->type     = MYSQLI_TYPE_VAR_STRING;
            $fields[] = $stdClass;
            return $fields;
        };

        $result = new Result();
        $result->setResult($mockMysqliResult);
        $result->setConnectionName('main');
        $result->setDatabase('bouh_world');

        $this
            ->if($hydrator = new \CCMBenchmark\Ting\Repository\Hydrator())
            ->and($hydrator->setMetadataRepository($services->get('MetadataRepository')))
            ->and($hydrator->setUnitOfWork($services->get('UnitOfWork')))
            ->and($hydrator->unserializeAliasWith('data', new Json(), ['assoc' => true]))
            ->then($iterator = $hydrator->setResult($result)->getIterator())
            ->then($result = $iterator->current())
            ->array($data = $result[0]->data)
            ->string($data['name'])
                ->isIdenticalTo('Sylvain');
    }

    public function testHydrateWithUnserializeAliasAndMapAlias()
    {
        $services = new \CCMBenchmark\Ting\Services();
        $services->get('MetadataRepository')
            ->batchLoadMetadata('tests\fixtures\model', __DIR__ . '/../../../fixtures/model/*Repository.php');

        $time = time();
        $mockMysqliResult = new \mock\tests\fixtures\FakeDriver\MysqliResult([['Sylvain', 'Robez-Masson', $time]]);
        $this->calling($mockMysqliResult)->fetch_fields = function () {
            $fields = [];
            $stdClass = new \stdClass();
            $stdClass->name     = 'fname';
            $stdClass->orgname  = 'boo_firstname';
            $stdClass->table    = 'bouh';
            $stdClass->orgtable = 'T_BOUH_BOO';
            $stdClass->type     = MYSQLI_TYPE_VAR_STRING;
            $fields[] = $stdClass;

            $stdClass = new \stdClass();
            $stdClass->name     = 'name';
            $stdClass->orgname  = 'boo_name';
            $stdClass->table    = 'bouh';
            $stdClass->orgtable = 'T_BOUH_BOO';
            $stdClass->type     = MYSQLI_TYPE_VAR_STRING;
            $fields[] = $stdClass;

            $stdClass = new \stdClass();
            $stdClass->name     = 'current_time';
            $stdClass->orgname  = '';
            $stdClass->table    = '';
            $stdClass->orgtable = '';
            $stdClass->type     = MYSQLI_TYPE_VAR_STRING;
            $fields[] = $stdClass;
            return $fields;
        };

        $result = new Result();
        $result->setResult($mockMysqliResult);
        $result->setConnectionName('main');
        $result->setDatabase('bouh_world');

        $this
            ->if($hydrator = new \CCMBenchmark\Ting\Repository\Hydrator())
            ->and($hydrator->setMetadataRepository($services->get('MetadataRepository')))
            ->and($hydrator->setUnitOfWork($services->get('UnitOfWork')))
            ->and($hydrator->mapAliasTo('current_time', 'bouh', 'setRetrievedTime'))
            ->and($hydrator->unserializeAliasWith('current_time', new DateTime(), ['format' => 'U']))
            ->then($iterator = $hydrator->setResult($result)->getIterator())
            ->then($data = $iterator->current())
            ->object($datetime = $data['bouh']->getRetrievedTime())
            ->integer($datetime->getTimestamp())
                ->isIdenticalTo($time);
    }

    public function testHydrateWithObjectDatabaseIsShouldHydrateToCity2()
    {
        $services = new \CCMBenchmark\Ting\Services();
        $services->get('MetadataRepository')
            ->batchLoadMetadata('tests\fixtures\model', __DIR__ . '/../../../fixtures/model/*Repository.php');

        $mockMysqliResult = new \mock\tests\fixtures\FakeDriver\MysqliResult([
            ['Sylvain', 'Robez-Masson', 3, 'Palaiseau']
        ]);
        $this->calling($mockMysqliResult)->fetch_fields = function () {
            $fields = [];
            $stdClass = new \stdClass();
            $stdClass->name     = 'fname';
            $stdClass->orgname  = 'boo_firstname';
            $stdClass->table    = 'bouh';
            $stdClass->orgtable = 'T_BOUH_BOO';
            $stdClass->type     = MYSQLI_TYPE_VAR_STRING;
            $fields[] = $stdClass;

            $stdClass = new \stdClass();
            $stdClass->name     = 'name';
            $stdClass->orgname  = 'boo_name';
            $stdClass->table    = 'bouh';
            $stdClass->orgtable = 'T_BOUH_BOO';
            $stdClass->type     = MYSQLI_TYPE_VAR_STRING;
            $fields[] = $stdClass;

            $stdClass = new \stdClass();
            $stdClass->name     = 'cityId';
            $stdClass->orgname  = 'cit_id';
            $stdClass->table    = 'cit';
            $stdClass->orgtable = 'T_CITY_CIT';
            $stdClass->type     = MYSQLI_TYPE_LONG;
            $fields[] = $stdClass;

            $stdClass = new \stdClass();
            $stdClass->name     = 'citname';
            $stdClass->orgname  = 'cit_name';
            $stdClass->table    = 'cit';
            $stdClass->orgtable = 'T_CITY_CIT';
            $stdClass->type     = MYSQLI_TYPE_VAR_STRING;
            $fields[] = $stdClass;
            return $fields;
        };

        $result = new Result();
        $result->setResult($mockMysqliResult);
        $result->setConnectionName('main');
        $result->setDatabase('bouh_world');

        $this
            ->if($hydrator = new \CCMBenchmark\Ting\Repository\Hydrator())
            ->and($hydrator->setMetadataRepository($services->get('MetadataRepository')))
            ->and($hydrator->setUnitOfWork($services->get('UnitOfWork')))
            ->and($hydrator->objectDatabaseIs('cit', 'bouh_world_2'))
            ->then($iterator = $hydrator->setResult($result)->getIterator())
            ->then($data = $iterator->current())
            ->object($data['cit'])
                ->isInstanceOf(\tests\fixtures\model\CitySecond::class);
    }

    public function testHydrateReturnSameResultWhenChangingPrimaryKeyOrder()
    {
        $services = new \CCMBenchmark\Ting\Services();

        $metaDataRepo = $services->get('MetadataRepository');

        $cityMetadata =  new \CCMBenchmark\Ting\Repository\Metadata(
            new \mock\CCMBenchmark\Ting\Serializer\SerializerFactoryInterface()
        );

        $cityMetadata->setEntity(City::class);
        $cityMetadata->setConnectionName('main');
        $cityMetadata->setDatabase('bouh_world');
        $cityMetadata->setTable('T_CITY_CIT');

        $cityMetadata->addField([
            'primary'       => true,
            'autoincrement' => true,
            'fieldName'     => 'id',
            'columnName'    => 'cit_id',
            'type'          => 'int'
        ]);

        $cityMetadata->addField([
            'fieldName'  => 'name',
            'columnName' => 'cit_name',
            'type'      => 'string'
        ]);

        $cityMetadata->addField([
            'fieldName'  => 'zipcode',
            'columnName' => 'cit_zipcode',
            'type'       => 'string'
        ]);


        $primaryMultiFieldMetadata = new \CCMBenchmark\Ting\Repository\Metadata(new \mock\CCMBenchmark\Ting\Serializer\SerializerFactoryInterface());

        $primaryMultiFieldMetadata->setEntity(PrimaryOnMultiField::class);
        $primaryMultiFieldMetadata->setConnectionName('main');
        $primaryMultiFieldMetadata->setDatabase('bouh_world');
        $primaryMultiFieldMetadata->setTable('T_PRIMARY_MULTI_FIELD');

        $primaryMultiFieldMetadata->addField([
            'primary'       => true,
            'fieldName'     => 'cityId',
            'columnName'    => 'city_id',
            'type'          => 'int'
        ]);

        $primaryMultiFieldMetadata->addField([
            'fieldName'  => 'otherItemId',
            'columnName' => 'other_item_id',
            'type'      => 'string',
            'primary'   => true
        ]);

        $primaryMultiFieldMetadata->addField([
            'fieldName'  => 'value',
            'columnName' => 'value',
            'type'       => 'string'
        ]);

        //*
        $primaryMultiFieldMetadataInverted = new \CCMBenchmark\Ting\Repository\Metadata(new \mock\CCMBenchmark\Ting\Serializer\SerializerFactoryInterface());
        $primaryMultiFieldMetadataInverted->setEntity(PrimaryOnMultiField::class);
        $primaryMultiFieldMetadataInverted->setConnectionName('main');
        $primaryMultiFieldMetadataInverted->setDatabase('bouh_world');
        $primaryMultiFieldMetadataInverted->setTable('T_PRIMARY_MULTI_FIELD');

        $primaryMultiFieldMetadataInverted->addField([
             'fieldName'  => 'otherItemId',
             'columnName' => 'other_item_id',
             'type'      => 'string',
             'primary'   => true
         ]);
        $primaryMultiFieldMetadataInverted->addField([
             'primary'       => true,
             'fieldName'     => 'cityId',
             'columnName'    => 'city_id',
             'type'          => 'int'
         ]);
        $primaryMultiFieldMetadataInverted->addField([
             'fieldName'  => 'value',
             'columnName' => 'value',
             'type'       => 'string'
         ]);
        //*/

        /** @var MetadataRepository $metadataRepo1 */
        $metadataRepo1 = clone $metaDataRepo;
        $metadataRepo2 = clone $metaDataRepo;

        $metadataRepo1->addMetadata(CityRepository::class, $cityMetadata);
        $metadataRepo1->addMetadata(PrimaryOnMultiField::class, $primaryMultiFieldMetadata);

        /** @var MetadataRepository $metadataRepo2 */
        $metadataRepo2->addMetadata(CityRepository::class, $cityMetadata);
        $metadataRepo2->addMetadata(PrimaryOnMultiField::class, $primaryMultiFieldMetadataInverted);

        $mysqliResult =  [
            [1, 'other_item1', 10, 'City1', 1],
            [1, 'other_item2', 20, 'City1', 1],
            [2, 'other_item1', 5, 'City2', 2],
            [2, 'other_item2', 8, 'City2', 2]
        ];

        $mockMysqliResult = new \mock\tests\fixtures\FakeDriver\MysqliResult($mysqliResult);
        $mockMysqliResult2 = new \mock\tests\fixtures\FakeDriver\MysqliResult($mysqliResult);

        $fetchFields = function () {
            $fields = [];
            $stdClass = new \stdClass();
            $stdClass->name     = 'cityId';
            $stdClass->orgname  = 'city_id';
            $stdClass->table    = 'primaryMultiField';
            $stdClass->orgtable = 'T_PRIMARY_MULTI_FIELD';
            $stdClass->type     = MYSQLI_TYPE_LONG;
            $fields[] = $stdClass;

            $stdClass = new \stdClass();
            $stdClass->name     = 'otherItemId';
            $stdClass->orgname  = 'other_item_id';
            $stdClass->table    = 'primaryMultiField';
            $stdClass->orgtable = 'T_PRIMARY_MULTI_FIELD';
            $stdClass->type     = MYSQLI_TYPE_VAR_STRING;
            $fields[] = $stdClass;

            $stdClass = new \stdClass();
            $stdClass->name     = 'value';
            $stdClass->orgname  = 'value';
            $stdClass->table    = 'primaryMultiField';
            $stdClass->orgtable = 'T_PRIMARY_MULTI_FIELD';
            $stdClass->type     = MYSQLI_TYPE_VAR_STRING;
            $fields[] = $stdClass;

            $stdClass = new \stdClass();
            $stdClass->name     = 'citname';
            $stdClass->orgname  = 'cit_name';
            $stdClass->table    = 'cit';
            $stdClass->orgtable = 'T_CITY_CIT';
            $stdClass->type     = MYSQLI_TYPE_VAR_STRING;
            $fields[] = $stdClass;

            $stdClass = new \stdClass();
            $stdClass->name     = 'cityId';
            $stdClass->orgname  = 'cit_id';
            $stdClass->table    = 'cit';
            $stdClass->orgtable = 'T_CITY_CIT';
            $stdClass->type     = MYSQLI_TYPE_LONG;
            $fields[] = $stdClass;

            return $fields;
        };

        $this->calling($mockMysqliResult)->fetch_fields = $fetchFields();
        $this->calling($mockMysqliResult2)->fetch_fields = $fetchFields();

        //*
        $sqlResult = new Result();
        $sqlResult->setResult($mockMysqliResult);
        $sqlResult->setConnectionName('main');
        $sqlResult->setDatabase('bouh_world');

        $sqlResult2 = new Result();
        $sqlResult2->setResult($mockMysqliResult2);
        $sqlResult2->setConnectionName('main');
        $sqlResult2->setDatabase('bouh_world');

        /** @var UnitOfWork $uow */
        $uow = $services->get('UnitOfWork');

        $this
            ->if($hydrator = new \CCMBenchmark\Ting\Repository\Hydrator())
                ->and($hydrator->setMetadataRepository($metadataRepo1))
                ->and($hydrator->setUnitOfWork($uow))
                ->and($hydrator->setResult($sqlResult))
                ->and($iterator = $hydrator->getIterator())
                ->and($uow->detachAll())
                ->and($result1 = iterator_to_array($iterator))

                ->and($hydrator2 = new \CCMBenchmark\Ting\Repository\Hydrator())
                ->and($hydrator2->setMetadataRepository($metadataRepo2))
                ->and($hydrator2->setUnitOfWork($uow))
                ->and($iterator2 = $hydrator2->setResult($sqlResult2)->getIterator())

            ->then($result2 = iterator_to_array($iterator2))
        ;

        $this->then()
                ->integer(count($result1))
                    ->isEqualTo(count($result2));

        foreach ($result1 as $i => $row) {
            $this
                ->integer($row['cit']->getId())
                   ->isEqualTo($result2[$i]['cit']->getId())
                ->string($row['cit']->getName())
                    ->isEqualTo($result2[$i]['cit']->getName())
            ;
            $this
                ->integer($row['primaryMultiField']->getCityId())
                    ->isEqualTo($result2[$i]['primaryMultiField']->getCityId())
                ->integer($row['primaryMultiField']->getValue())
                    ->isEqualTo($result2[$i]['primaryMultiField']->getValue())
                ->string($row['primaryMultiField']->getOtherItemId())
                    ->isEqualTo($result2[$i]['primaryMultiField']->getOtherItemId())
            ;
        }
    }

    public function testHydrateWithNullSQLReturnShouldReturnNull()
    {
        $services = new \CCMBenchmark\Ting\Services();
        $services->get('MetadataRepository')
                 ->batchLoadMetadata('tests\fixtures\model', __DIR__ . '/../../../fixtures/model/*Repository.php');

        $mockMysqliResult = new \mock\tests\fixtures\FakeDriver\MysqliResult([
            ['Sylvain', 'Robez-Masson', null, null]
        ]);
        $this->calling($mockMysqliResult)->fetch_fields = function () {
            $fields = [];
            $stdClass = new \stdClass();
            $stdClass->name = 'fname';
            $stdClass->orgname = 'boo_firstname';
            $stdClass->table = 'bouh';
            $stdClass->orgtable = 'T_BOUH_BOO';
            $stdClass->type = MYSQLI_TYPE_VAR_STRING;
            $fields[] = $stdClass;

            $stdClass = new \stdClass();
            $stdClass->name = 'name';
            $stdClass->orgname = 'boo_name';
            $stdClass->table = 'bouh';
            $stdClass->orgtable = 'T_BOUH_BOO';
            $stdClass->type = MYSQLI_TYPE_VAR_STRING;
            $fields[] = $stdClass;

            $stdClass = new \stdClass();
            $stdClass->name = 'cityId';
            $stdClass->orgname = 'cit_id';
            $stdClass->table = 'cit';
            $stdClass->orgtable = 'T_CITY_CIT';
            $stdClass->type = MYSQLI_TYPE_LONG;
            $fields[] = $stdClass;

            $stdClass = new \stdClass();
            $stdClass->name = 'citname';
            $stdClass->orgname = 'cit_name';
            $stdClass->table = 'cit';
            $stdClass->orgtable = 'T_CITY_CIT';
            $stdClass->type = MYSQLI_TYPE_VAR_STRING;
            $fields[] = $stdClass;

            return $fields;
        };

        $result = new Result();
        $result->setResult($mockMysqliResult);
        $result->setConnectionName('main');
        $result->setDatabase('bouh_world');

        $this
            ->if($hydrator = new \CCMBenchmark\Ting\Repository\Hydrator())
                ->and($hydrator->setMetadataRepository($services->get('MetadataRepository')))
                ->and($hydrator->setUnitOfWork($services->get('UnitOfWork')))
                ->and($hydrator->objectDatabaseIs('cit', 'bouh_world_2'))
            ->then($data = $hydrator->setResult($result)->getIterator()->current())
                ->variable($data['cit'])
                    ->isNull();
    }


    public function testHydrateWithIdentityMapFalseShouldReturnNewEntity()
    {
        $services = new \CCMBenchmark\Ting\Services();
        $services->get('MetadataRepository')
            ->batchLoadMetadata('tests\fixtures\model', __DIR__ . '/../../../fixtures/model/*Repository.php');

        $mockMysqliResult = new \mock\tests\fixtures\FakeDriver\MysqliResult([
            [23, 'Michael', 'Jordan'],
            [23, 'Michael', 'Jordan']
        ]);

        $this->calling($mockMysqliResult)->fetch_fields = function () {
            $fields = [];

            $stdClass = new \stdClass();
            $stdClass->name     = 'id';
            $stdClass->orgname  = 'boo_id';
            $stdClass->table    = 'bouh';
            $stdClass->orgtable = 'T_BOUH_BOO';
            $stdClass->type     = MYSQLI_TYPE_LONG;
            $fields[] = $stdClass;

            $stdClass = new \stdClass();
            $stdClass->name     = 'name';
            $stdClass->orgname  = 'boo_name';
            $stdClass->table    = 'bouh';
            $stdClass->orgtable = 'T_BOUH_BOO';
            $stdClass->type     = MYSQLI_TYPE_VAR_STRING;
            $fields[] = $stdClass;

            $stdClass = new \stdClass();
            $stdClass->name     = 'firstname';
            $stdClass->orgname  = 'boo_firstname';
            $stdClass->table    = 'bouh';
            $stdClass->orgtable = 'T_BOUH_BOO';
            $stdClass->type     = MYSQLI_TYPE_VAR_STRING;
            $fields[] = $stdClass;

            return $fields;
        };

        $result = new Result();
        $result->setResult($mockMysqliResult);
        $result->setConnectionName('main');
        $result->setDatabase('bouh_world');

        $this
            ->if($hydrator = new \CCMBenchmark\Ting\Repository\Hydrator())
                ->and($hydrator->setMetadataRepository($services->get('MetadataRepository')))
                ->and($hydrator->setUnitOfWork($services->get('UnitOfWork')))
            ->then($iterator = $hydrator->setResult($result)->getIterator())
            ->then($currentObject = $iterator->current()['bouh'])
            ->then($iterator->next())
            ->then($nextObject = $iterator->current()['bouh'])
            ->string(spl_object_hash($currentObject))
            ->isNotEqualTo(spl_object_hash($nextObject))
        ;
    }

    public function testHydrateWithIdentityMapTrueShouldReturnSameEntity()
    {
        $services = new \CCMBenchmark\Ting\Services();
        $services->get('MetadataRepository')
            ->batchLoadMetadata('tests\fixtures\model', __DIR__ . '/../../../fixtures/model/*Repository.php');

        $mockMysqliResult = new \mock\tests\fixtures\FakeDriver\MysqliResult([
            [23, 'LeBron', 'James', 'Cleveland'],
            [30, 'Stephen', 'Curry', 'San Francisco'],
            [23, 'LeBron', 'James', 'Los Angeles']
        ]);

        $this->calling($mockMysqliResult)->fetch_fields = function () {
            $fields = [];

            $stdClass = new \stdClass();
            $stdClass->name     = 'id';
            $stdClass->orgname  = 'boo_id';
            $stdClass->table    = 'bouh';
            $stdClass->orgtable = 'T_BOUH_BOO';
            $stdClass->type     = MYSQLI_TYPE_LONG;
            $fields[] = $stdClass;

            $stdClass = new \stdClass();
            $stdClass->name     = 'name';
            $stdClass->orgname  = 'boo_name';
            $stdClass->table    = 'bouh';
            $stdClass->orgtable = 'T_BOUH_BOO';
            $stdClass->type     = MYSQLI_TYPE_VAR_STRING;
            $fields[] = $stdClass;

            $stdClass = new \stdClass();
            $stdClass->name     = 'firstname';
            $stdClass->orgname  = 'boo_firstname';
            $stdClass->table    = 'bouh';
            $stdClass->orgtable = 'T_BOUH_BOO';
            $stdClass->type     = MYSQLI_TYPE_VAR_STRING;
            $fields[] = $stdClass;

            $stdClass = new \stdClass();
            $stdClass->name     = 'cityName';
            $stdClass->orgname  = '';
            $stdClass->table    = '';
            $stdClass->orgtable = '';
            $stdClass->type     = MYSQLI_TYPE_VAR_STRING;
            $fields[] = $stdClass;

            return $fields;
        };

        $result = new Result();
        $result->setResult($mockMysqliResult);
        $result->setConnectionName('main');
        $result->setDatabase('bouh_world');

        $this
            ->if($hydrator = new \CCMBenchmark\Ting\Repository\Hydrator())
            ->and($hydrator->setMetadataRepository($services->get('MetadataRepository')))
            ->and($hydrator->setUnitOfWork($services->get('UnitOfWork')))
            ->and($hydrator->identityMap(true))
            ->then($iterator = $hydrator->setResult($result)->getIterator())
            ->then($currentObject = $iterator->current()['bouh'])
            ->then($iterator->next())
            ->then($iterator->next())
            ->then($nextObject = $iterator->current()['bouh'])
            ->string(spl_object_hash($currentObject))
            ->isEqualTo(spl_object_hash($nextObject))
        ;
    }
}
