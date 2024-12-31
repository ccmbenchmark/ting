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
use atoum;

/**
 * HydratorAggregator
 */
class HydratorAggregator extends atoum
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
            'fieldName'  => 'name',
            'columnName' => 'cit_name',
            'type'       => 'string'
        ]);

        $services->get('MetadataRepository')->addMetadata('tests\fixtures\model\CityRepository', $metadata);

        $mockMysqliResult = new \mock\tests\fixtures\FakeDriver\MysqliResult([
            [4, 'Xavier', 'Leune', 'Boulogne-Billancourt'],
            [4, 'Xavier', 'Leune', 'Palaiseau'],
            [3, 'Sylvain', 'Robez-Masson', 'Palaiseau'],
            [3, 'Sylvain', 'Robez-Masson', 'Montbéliard'],
            [3, 'Sylvain', 'Robez-Masson', 'Luxiol']
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
            ->if($hydrator = new \CCMBenchmark\Ting\Repository\HydratorAggregator())
            ->and($hydrator->setMetadataRepository($services->get('MetadataRepository')))
            ->and($hydrator->setUnitOfWork($services->get('UnitOfWork')))
            ->and($hydrator->callableDataIs(fn ($result) => $result['c']))
            ->and($hydrator->callableIdIs(fn ($result) => $result['bouh']->getId()))
            ->then($iterator = $hydrator->setResult($result)->getIterator())
            ->then($data = $iterator->current())
            ->string($data['bouh']->getName())
                ->isIdenticalTo('Leune')
            ->string($data['bouh']->getFirstname())
                ->isIdenticalTo('Xavier')
            ->string($data['aggregate'][0]->getName())
                ->isIdenticalTo('Boulogne-Billancourt')
            ->string($data['aggregate'][1]->getName())
                ->isIdenticalTo('Palaiseau')
            ->then($iterator->next())
            ->then($data = $iterator->current())
            ->string($data['bouh']->getName())
                ->isIdenticalTo('Robez-Masson')
            ->string($data['bouh']->getFirstname())
                ->isIdenticalTo('Sylvain')
            ->string($data['aggregate'][0]->getName())
                ->isIdenticalTo('Palaiseau')
            ->string($data['aggregate'][1]->getName())
                ->isIdenticalTo('Montbéliard')
            ->string($data['aggregate'][2]->getName())
                ->isIdenticalTo('Luxiol')
        ;
    }

    public function testHydrateWithFinalize()
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
            'fieldName'  => 'name',
            'columnName' => 'cit_name',
            'type'       => 'string'
        ]);

        $services->get('MetadataRepository')->addMetadata('tests\fixtures\model\CityRepository', $metadata);

        $mockMysqliResult = new \mock\tests\fixtures\FakeDriver\MysqliResult([
            [4, 'Xavier', 'Leune', 'Boulogne-Billancourt'],
            [4, 'Xavier', 'Leune', 'Palaiseau'],
            [3, 'Sylvain', 'Robez-Masson', 'Palaiseau'],
            [3, 'Sylvain', 'Robez-Masson', 'Montbéliard'],
            [3, 'Sylvain', 'Robez-Masson', 'Luxiol']
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
            ->if($hydrator = new \CCMBenchmark\Ting\Repository\HydratorAggregator())
            ->and($hydrator->setMetadataRepository($services->get('MetadataRepository')))
            ->and($hydrator->setUnitOfWork($services->get('UnitOfWork')))
            ->and($hydrator->callableDataIs(fn ($result) => $result['c']))
            ->and($hydrator->callableIdIs(fn ($result) => $result['bouh']->getId()))
            ->and($hydrator->callableFinalizeAggregate(function ($result, $aggregate) {
                $result['bouh']->aggregate = $aggregate;
                return $result['bouh'];
            }))
            ->then($iterator = $hydrator->setResult($result)->getIterator())
            ->then($data = $iterator->current())
            ->string($data->getName())
                ->isIdenticalTo('Leune')
            ->string($data->getFirstname())
                ->isIdenticalTo('Xavier')
            ->string($data->aggregate[0]->getName())
                ->isIdenticalTo('Boulogne-Billancourt')
            ->string($data->aggregate[1]->getName())
                ->isIdenticalTo('Palaiseau')
            ->then($iterator->next())
            ->then($data = $iterator->current())
            ->string($data->getName())
                ->isIdenticalTo('Robez-Masson')
            ->string($data->getFirstname())
                ->isIdenticalTo('Sylvain')
            ->string($data->aggregate[0]->getName())
                ->isIdenticalTo('Palaiseau')
            ->string($data->aggregate[1]->getName())
                ->isIdenticalTo('Montbéliard')
            ->string($data->aggregate[2]->getName())
                ->isIdenticalTo('Luxiol')
        ;
    }

    public function testHydrateWithoutRightSortShouldContinue()
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
            'fieldName'  => 'name',
            'columnName' => 'cit_name',
            'type'       => 'string'
        ]);

        $services->get('MetadataRepository')->addMetadata('tests\fixtures\model\CityRepository', $metadata);

        $mockMysqliResult = new \mock\tests\fixtures\FakeDriver\MysqliResult([
            [4, 'Xavier', 'Leune', 'Boulogne-Billancourt'],
            [3, 'Sylvain', 'Robez-Masson', 'Palaiseau'],
            [4, 'Xavier', 'Leune', 'Palaiseau'],
            [3, 'Sylvain', 'Robez-Masson', 'Montbéliard'],
            [3, 'Sylvain', 'Robez-Masson', 'Luxiol']
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
            ->if($hydrator = new \CCMBenchmark\Ting\Repository\HydratorAggregator())
            ->and($hydrator->setMetadataRepository($services->get('MetadataRepository')))
            ->and($hydrator->setUnitOfWork($services->get('UnitOfWork')))
            ->and($hydrator->callableDataIs(fn ($result) => $result['c']))
            ->and($hydrator->callableIdIs(fn ($result) => $result['bouh']->getId()))
            ->then($iterator = $hydrator->setResult($result)->getIterator())
            ->then($data = $iterator->current())
            ->string($data['bouh']->getName())
                ->isIdenticalTo('Leune')
            ->string($data['bouh']->getFirstname())
                ->isIdenticalTo('Xavier')
            ->string($data['aggregate'][0]->getName())
                ->isIdenticalTo('Boulogne-Billancourt')
            ->then($iterator->next())
            ->then($data = $iterator->current())
            ->string($data['bouh']->getName())
                ->isIdenticalTo('Robez-Masson')
            ->string($data['bouh']->getFirstname())
                ->isIdenticalTo('Sylvain')
            ->string($data['aggregate'][0]->getName())
                ->isIdenticalTo('Palaiseau')
            ->string($data['aggregate'][1]->getName())
                ->isIdenticalTo('Montbéliard')
            ->string($data['aggregate'][2]->getName())
                ->isIdenticalTo('Luxiol')
        ;
    }
}
