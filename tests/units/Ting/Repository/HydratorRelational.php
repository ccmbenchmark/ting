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

use CCMBenchmark\Ting\Repository\Hydrator\RelationOne;
use CCMBenchmark\Ting\Services;
use CCMBenchmark\Ting\Driver\Mysqli\Result;
use CCMBenchmark\Ting\Repository\Hydrator\RelationMany;
use mageekguy\atoum;

/**
 * HydratorRelational
 */
class HydratorRelational extends atoum
{
    /**
     * @var Services
     */
    private $services;

    private function getResult()
    {
        $this->services = new Services();
        $metadata = new \CCMBenchmark\Ting\Repository\Metadata($this->services->get('SerializerFactory'));
        $metadata->setConnectionName('connectionName');
        $metadata->setDatabase('database');
        $metadata->setEntity('tests\fixtures\model\Bouh');
        $metadata->setTable('T_BOUH_BOO');

        $metadata->addField([
            'fieldName'  => 'id',
            'columnName' => 'boo_id',
            'type'       => 'int'
        ]);

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

        $this->services->get('MetadataRepository')->addMetadata('tests\fixtures\model\BouhRepository', $metadata);

        $metadata = new \CCMBenchmark\Ting\Repository\Metadata($this->services->get('SerializerFactory'));
        $metadata->setConnectionName('connectionName');
        $metadata->setDatabase('database');
        $metadata->setEntity('tests\fixtures\model\City');
        $metadata->setTable('T_CITY_CIT');

        $metadata->addField([
            'fieldName'  => 'id',
            'columnName' => 'cit_id',
            'type'       => 'int'
        ]);

        $metadata->addField([
            'fieldName'  => 'name',
            'columnName' => 'cit_name',
            'type'       => 'string'
        ]);

        $this->services->get('MetadataRepository')->addMetadata('tests\fixtures\model\CityRepository', $metadata);

        $mockMysqliResult = new \mock\tests\fixtures\FakeDriver\MysqliResult([
            [4, 'Xavier', 'Leune', 1, 'Boulogne-Billancourt'],
            [4, 'Xavier', 'Leune', 2, 'Palaiseau'],
            [3, 'Sylvain', 'Robez-Masson', 2, 'Palaiseau'],
            [3, 'Sylvain', 'Robez-Masson', 3, 'Montbéliard'],
            [3, 'Sylvain', 'Robez-Masson', 4, 'Luxiol']
        ]);
        $this->calling($mockMysqliResult)->fetch_fields = function () {
            $fields = [];

            $stdClass = new \stdClass();
            $stdClass->name     = 'id';
            $stdClass->orgname  = 'boo_id';
            $stdClass->table    = 'bouh';
            $stdClass->orgtable = 'T_BOUH_BOO';
            $stdClass->type     = MYSQLI_TYPE_VAR_STRING;
            $fields[] = $stdClass;

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
            $stdClass->name     = 'id';
            $stdClass->orgname  = 'cit_id';
            $stdClass->table    = 'c';
            $stdClass->orgtable = 'T_CITY_CIT';
            $stdClass->type     = MYSQLI_TYPE_VAR_STRING;
            $fields[] = $stdClass;

            $stdClass = new \stdClass();
            $stdClass->name     = 'name';
            $stdClass->orgname  = 'cit_name';
            $stdClass->table    = 'c';
            $stdClass->orgtable = 'T_CITY_CIT';
            $stdClass->type     = MYSQLI_TYPE_VAR_STRING;
            $fields[] = $stdClass;
            return $fields;
        };

        $result = new Result();
        $result->setResult($mockMysqliResult);
        $result->setConnectionName('connectionName');
        $result->setDatabase('database');

        return $result;
    }

    public function testHydrate()
    {
        $this
            ->if($result = $this->getResult())
            ->and($hydrator = new \CCMBenchmark\Ting\Repository\HydratorRelational())
            ->and($hydrator->setMetadataRepository($this->services->get('MetadataRepository')))
            ->and($hydrator->setUnitOfWork($this->services->get('UnitOfWork')))
            ->and($hydrator->addRelation((new RelationMany())->aggregate('c', 'getId')->to('bouh', 'getId')->setter('citiesAre')))
            ->then($iterator = $hydrator->setResult($result)->getIterator())
            ->then($data = $iterator->current())
            ->string($data['bouh']->getName())
                ->isIdenticalTo('Leune')
            ->string($data['bouh']->getFirstname())
                ->isIdenticalTo('Xavier')
            ->array($cities = $data['bouh']->getCities())
            ->string(reset($cities)->getName())
                ->isIdenticalTo('Boulogne-Billancourt')
            ->string(next($cities)->getName())
                ->isIdenticalTo('Palaiseau')
            ->then($iterator->next())
            ->then($data = $iterator->current())
            ->string($data['bouh']->getName())
                ->isIdenticalTo('Robez-Masson')
            ->string($data['bouh']->getFirstname())
                ->isIdenticalTo('Sylvain')
            ->array($cities = $data['bouh']->getCities())
            ->string(reset($cities)->getName())
                ->isIdenticalTo('Palaiseau')
            ->string(next($cities)->getName())
                ->isIdenticalTo('Montbéliard')
            ->string(next($cities)->getName())
                ->isIdenticalTo('Luxiol')
            ;
    }

    public function testHydrateWithDefaultPk()
    {
        $result = $this->getResult();

        $this->services->get('MetadataRepository')->addMetadata(
            'tests\fixtures\model\CityRepository',
            \tests\fixtures\model\CityRepository::initMetadata($this->services->get('SerializerFactory'))
        );

        $this->services->get('MetadataRepository')->addMetadata(
            'tests\fixtures\model\BouhRepository',
            \tests\fixtures\model\BouhRepository::initMetadata($this->services->get('SerializerFactory'))
        );

        $this
            ->if($hydrator = new \CCMBenchmark\Ting\Repository\HydratorRelational())
            ->and($hydrator->setMetadataRepository($this->services->get('MetadataRepository')))
            ->and($hydrator->setUnitOfWork($this->services->get('UnitOfWork')))
            ->and($hydrator->addRelation((new RelationMany())->aggregate('c')->to('bouh')->setter('citiesAre')))
            ->then($iterator = $hydrator->setResult($result)->getIterator())
            ->then($data = $iterator->current())
            ->string($data['bouh']->getName())
                ->isIdenticalTo('Leune')
            ->string($data['bouh']->getFirstname())
                ->isIdenticalTo('Xavier')
            ->array($cities = $data['bouh']->getCities())
            ->string(reset($cities)->getName())
                ->isIdenticalTo('Boulogne-Billancourt')
            ->string(next($cities)->getName())
                ->isIdenticalTo('Palaiseau')
            ->then($iterator->next())
            ->then($data = $iterator->current())
            ->string($data['bouh']->getName())
                ->isIdenticalTo('Robez-Masson')
            ->string($data['bouh']->getFirstname())
                ->isIdenticalTo('Sylvain')
            ->array($cities = $data['bouh']->getCities())
            ->string(reset($cities)->getName())
                ->isIdenticalTo('Palaiseau')
            ->string(next($cities)->getName())
                ->isIdenticalTo('Montbéliard')
            ->string(next($cities)->getName())
                ->isIdenticalTo('Luxiol')
        ;
    }

    public function testHydrateRelationOne()
    {
        $this
            ->if($result = $this->getResult())
            ->and($hydrator = new \CCMBenchmark\Ting\Repository\HydratorRelational())
            ->and($hydrator->setMetadataRepository($this->services->get('MetadataRepository')))
            ->and($hydrator->setUnitOfWork($this->services->get('UnitOfWork')))
            ->and($hydrator->addRelation((new RelationOne())->aggregate('c', 'getId')->to('bouh', 'getId')->setter('setCity')))
            ->then($iterator = $hydrator->setResult($result)->getIterator())
            ->then($data = $iterator->current())
            ->string($data['bouh']->getName())
                ->isIdenticalTo('Leune')
            ->string($data['bouh']->getFirstname())
                ->isIdenticalTo('Xavier')
            ->string($data['bouh']->getCity()->getName())
                ->isIdenticalTo('Palaiseau')
            ->then($iterator->next())
            ->then($data = $iterator->current())
            ->string($data['bouh']->getName())
                ->isIdenticalTo('Robez-Masson')
            ->string($data['bouh']->getFirstname())
                ->isIdenticalTo('Sylvain')
            ->string($data['bouh']->getCity()->getName())
                ->isIdenticalTo('Luxiol');
        ;
    }

    public function testHydrateWithFinalize()
    {
        $this
            ->if($result = $this->getResult())
            ->and($hydrator = new \CCMBenchmark\Ting\Repository\HydratorRelational())
            ->and($hydrator->setMetadataRepository($this->services->get('MetadataRepository')))
            ->and($hydrator->setUnitOfWork($this->services->get('UnitOfWork')))
            ->and($hydrator->addRelation((new RelationMany())->aggregate('c', 'getId')->to('bouh', 'getId')->setter('citiesAre')))
            ->and($hydrator->callableFinalizeAggregate(function ($result) {
                return $result['bouh'];
            }))
            ->then($iterator = $hydrator->setResult($result)->getIterator())
            ->then($data = $iterator->current())
            ->string($data->getName())
                ->isIdenticalTo('Leune')
            ->string($data->getFirstname())
                ->isIdenticalTo('Xavier')
            ->array($cities = $data->getCities())
            ->string(reset($cities)->getName())
                ->isIdenticalTo('Boulogne-Billancourt')
            ->string(next($cities)->getName())
                ->isIdenticalTo('Palaiseau')
            ->then($iterator->next())
            ->then($data = $iterator->current())
            ->string($data->getName())
                ->isIdenticalTo('Robez-Masson')
            ->string($data->getFirstname())
                ->isIdenticalTo('Sylvain')
            ->array($cities = $data->getCities())
            ->string(reset($cities)->getName())
                ->isIdenticalTo('Palaiseau')
            ->string(next($cities)->getName())
                ->isIdenticalTo('Montbéliard')
            ->string(next($cities)->getName())
                ->isIdenticalTo('Luxiol')
        ;
    }

    public function testHydrateWithoutRightSort()
    {
        $services = new \CCMBenchmark\Ting\Services();
        $metadata = new \CCMBenchmark\Ting\Repository\Metadata($services->get('SerializerFactory'));
        $metadata->setConnectionName('connectionName');
        $metadata->setDatabase('database');
        $metadata->setEntity('tests\fixtures\model\Bouh');
        $metadata->setTable('T_BOUH_BOO');

        $metadata->addField([
            'fieldName'  => 'id',
            'columnName' => 'boo_id',
            'type'       => 'int'
        ]);

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

        $metadata = new \CCMBenchmark\Ting\Repository\Metadata($services->get('SerializerFactory'));
        $metadata->setConnectionName('connectionName');
        $metadata->setDatabase('database');
        $metadata->setEntity('tests\fixtures\model\City');
        $metadata->setTable('T_CITY_CIT');

        $metadata->addField([
            'fieldName'  => 'id',
            'columnName' => 'cit_id',
            'type'       => 'int'
        ]);

        $metadata->addField([
            'fieldName'  => 'name',
            'columnName' => 'cit_name',
            'type'       => 'string'
        ]);

        $services->get('MetadataRepository')->addMetadata('tests\fixtures\model\CityRepository', $metadata);

        $mockMysqliResult = new \mock\tests\fixtures\FakeDriver\MysqliResult([
            [4, 'Xavier', 'Leune', 1, 'Boulogne-Billancourt'],
            [3, 'Sylvain', 'Robez-Masson', 2, 'Palaiseau'],
            [4, 'Xavier', 'Leune', 2, 'Palaiseau'],
            [3, 'Sylvain', 'Robez-Masson', 3, 'Montbéliard'],
            [3, 'Sylvain', 'Robez-Masson', 4, 'Luxiol']
        ]);
        $this->calling($mockMysqliResult)->fetch_fields = function () {
            $fields = [];

            $stdClass = new \stdClass();
            $stdClass->name     = 'id';
            $stdClass->orgname  = 'boo_id';
            $stdClass->table    = 'bouh';
            $stdClass->orgtable = 'T_BOUH_BOO';
            $stdClass->type     = MYSQLI_TYPE_VAR_STRING;
            $fields[] = $stdClass;

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
            $stdClass->name     = 'id';
            $stdClass->orgname  = 'cit_id';
            $stdClass->table    = 'c';
            $stdClass->orgtable = 'T_CITY_CIT';
            $stdClass->type     = MYSQLI_TYPE_VAR_STRING;
            $fields[] = $stdClass;

            $stdClass = new \stdClass();
            $stdClass->name     = 'name';
            $stdClass->orgname  = 'cit_name';
            $stdClass->table    = 'c';
            $stdClass->orgtable = 'T_CITY_CIT';
            $stdClass->type     = MYSQLI_TYPE_VAR_STRING;
            $fields[] = $stdClass;
            return $fields;
        };

        $result = new Result();
        $result->setResult($mockMysqliResult);
        $result->setConnectionName('connectionName');
        $result->setDatabase('database');

        $this
            ->if($hydrator = new \CCMBenchmark\Ting\Repository\HydratorRelational())
            ->and($hydrator->setMetadataRepository($services->get('MetadataRepository')))
            ->and($hydrator->setUnitOfWork($services->get('UnitOfWork')))
            ->and($hydrator->addRelation((new RelationMany())->aggregate('c', 'getId')->to('bouh', 'getId')->setter('citiesAre')))
            ->then($iterator = $hydrator->setResult($result)->getIterator())
            ->then($data = $iterator->current())
            ->string($data['bouh']->getName())
                ->isIdenticalTo('Leune')
            ->string($data['bouh']->getFirstname())
                ->isIdenticalTo('Xavier')
            ->array($cities = $data['bouh']->getCities())
            ->string(reset($cities)->getName())
                ->isIdenticalTo('Boulogne-Billancourt')
            ->string(next($cities)->getName())
                ->isIdenticalTo('Palaiseau')
            ->then($iterator->next())
            ->then($data = $iterator->current())
            ->string($data['bouh']->getName())
                ->isIdenticalTo('Robez-Masson')
            ->string($data['bouh']->getFirstname())
                ->isIdenticalTo('Sylvain')
            ->array($cities = $data['bouh']->getCities())
            ->string(reset($cities)->getName())
                ->isIdenticalTo('Palaiseau')
            ->string(next($cities)->getName())
                ->isIdenticalTo('Montbéliard')
            ->string(next($cities)->getName())
                ->isIdenticalTo('Luxiol')
        ;
    }

    public function testHydrateWithDepth2()
    {
        $services = new \CCMBenchmark\Ting\Services();
        $metadata = new \CCMBenchmark\Ting\Repository\Metadata($services->get('SerializerFactory'));
        $metadata->setConnectionName('connectionName');
        $metadata->setDatabase('database');
        $metadata->setEntity('tests\fixtures\model\Bouh');
        $metadata->setTable('T_BOUH_BOO');

        $metadata->addField([
            'fieldName'  => 'id',
            'columnName' => 'boo_id',
            'type'       => 'int'
        ]);

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

        $metadata = new \CCMBenchmark\Ting\Repository\Metadata($services->get('SerializerFactory'));
        $metadata->setConnectionName('connectionName');
        $metadata->setDatabase('database');
        $metadata->setEntity('tests\fixtures\model\City');
        $metadata->setTable('T_CITY_CIT');

        $metadata->addField([
            'fieldName'  => 'id',
            'columnName' => 'cit_id',
            'type'       => 'int'
        ]);

        $metadata->addField([
            'fieldName'  => 'name',
            'columnName' => 'cit_name',
            'type'       => 'string'
        ]);

        $services->get('MetadataRepository')->addMetadata('tests\fixtures\model\CityRepository', $metadata);

        $services->get('MetadataRepository')->addMetadata(
            'tests\fixtures\model\ParkRepository',
            \tests\fixtures\model\ParkRepository::initMetadata($services->get('SerializerFactory'))
        );

        $mockMysqliResult = new \mock\tests\fixtures\FakeDriver\MysqliResult([
            [4, 'Xavier', 'Leune', 1, 'Boulogne-Billancourt', 1, 'Parc de Billancourt'],
            [3, 'Sylvain', 'Robez-Masson', 2, 'Palaiseau', 2, 'Parc Pierre et Marie Curie'],
            [4, 'Xavier', 'Leune', 2, 'Palaiseau', null, null],
            [3, 'Sylvain', 'Robez-Masson', 3, 'Montbéliard', null, null],
            [4, 'Xavier', 'Leune', 1, 'Boulogne-Billancourt', 3, 'Parc de Boulogne-Edmond-de-Rothschild'],
            [3, 'Sylvain', 'Robez-Masson', 2, 'Palaiseau', 4, 'Square du Pileu'],
            [3, 'Sylvain', 'Robez-Masson', 2, 'Palaiseau', 5, 'Bois du Clos du Pileu'],
            [3, 'Sylvain', 'Robez-Masson', 4, 'Luxiol', null, null]
        ]);
        $this->calling($mockMysqliResult)->fetch_fields = function () {
            $fields = [];

            $stdClass = new \stdClass();
            $stdClass->name     = 'id';
            $stdClass->orgname  = 'boo_id';
            $stdClass->table    = 'bouh';
            $stdClass->orgtable = 'T_BOUH_BOO';
            $stdClass->type     = MYSQLI_TYPE_VAR_STRING;
            $fields[] = $stdClass;

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
            $stdClass->name     = 'id';
            $stdClass->orgname  = 'cit_id';
            $stdClass->table    = 'c';
            $stdClass->orgtable = 'T_CITY_CIT';
            $stdClass->type     = MYSQLI_TYPE_VAR_STRING;
            $fields[] = $stdClass;

            $stdClass = new \stdClass();
            $stdClass->name     = 'name';
            $stdClass->orgname  = 'cit_name';
            $stdClass->table    = 'c';
            $stdClass->orgtable = 'T_CITY_CIT';
            $stdClass->type     = MYSQLI_TYPE_VAR_STRING;
            $fields[] = $stdClass;

            $stdClass = new \stdClass();
            $stdClass->name     = 'id';
            $stdClass->orgname  = 'pa_id';
            $stdClass->table    = 'park';
            $stdClass->orgtable = 'T_PARK_PA';
            $stdClass->type     = MYSQLI_TYPE_VAR_STRING;
            $fields[] = $stdClass;

            $stdClass = new \stdClass();
            $stdClass->name     = 'name';
            $stdClass->orgname  = 'pa_name';
            $stdClass->table    = 'park';
            $stdClass->orgtable = 'T_PARK_PA';
            $stdClass->type     = MYSQLI_TYPE_VAR_STRING;
            $fields[] = $stdClass;
            return $fields;
        };

        $result = new Result();
        $result->setResult($mockMysqliResult);
        $result->setConnectionName('connectionName');
        $result->setDatabase('database');

        $this
            ->if($hydrator = new \CCMBenchmark\Ting\Repository\HydratorRelational())
            ->and($hydrator->setMetadataRepository($services->get('MetadataRepository')))
            ->and($hydrator->setUnitOfWork($services->get('UnitOfWork')))
            ->and($hydrator->addRelation((new RelationMany())->aggregate('c', 'getId')->to('bouh', 'getId')->setter('citiesAre')))
            ->and($hydrator->addRelation((new RelationMany())->aggregate('park', 'getId')->to('c', 'getId')->setter('parksAre')))
            ->then($iterator = $hydrator->setResult($result)->getIterator())
            ->then($data = $iterator->current())
            ->string($data['bouh']->getName())
                ->isIdenticalTo('Leune')
            ->string($data['bouh']->getFirstname())
                ->isIdenticalTo('Xavier')
            ->array($cities = $data['bouh']->getCities())
            ->string(reset($cities)->getName())
                ->isIdenticalTo('Boulogne-Billancourt')
            ->array($parks = reset($cities)->getParks())
            ->string(reset($parks)->getName())
                ->isIdenticalTo('Parc de Billancourt')
            ->string(next($parks)->getName())
                ->isIdenticalTo('Parc de Boulogne-Edmond-de-Rothschild')
            ->string(next($cities)->getName())
                ->isIdenticalTo('Palaiseau')
            ->array($parks = current($cities)->getParks())
            ->string(reset($parks)->getName())
                ->isIdenticalTo('Parc Pierre et Marie Curie')
            ->string(next($parks)->getName())
                ->isIdenticalTo('Square du Pileu')
            ->string(next($parks)->getName())
                ->isIdenticalTo('Bois du Clos du Pileu')
            ->then($iterator->next())
            ->then($data = $iterator->current())
            ->string($data['bouh']->getName())
                ->isIdenticalTo('Robez-Masson')
            ->string($data['bouh']->getFirstname())
                ->isIdenticalTo('Sylvain')
            ->array($cities = $data['bouh']->getCities())
            ->string(reset($cities)->getName())
                ->isIdenticalTo('Palaiseau')
            ->array($parks = current($cities)->getParks())
            ->string(reset($parks)->getName())
                ->isIdenticalTo('Parc Pierre et Marie Curie')
            ->string(next($parks)->getName())
                ->isIdenticalTo('Square du Pileu')
            ->string(next($parks)->getName())
                ->isIdenticalTo('Bois du Clos du Pileu')
            ->string(next($cities)->getName())
                ->isIdenticalTo('Montbéliard')
            ->string(next($cities)->getName())
                ->isIdenticalTo('Luxiol')
        ;
    }
}
