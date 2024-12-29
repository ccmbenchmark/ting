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

class HydratorSingleObject extends atoum
{
    public function testHydrateShouldReturnBouhObject()
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
            ->if($hydrator = new \CCMBenchmark\Ting\Repository\HydratorSingleObject())
            ->and($hydrator->setMetadataRepository($services->get('MetadataRepository')))
            ->and($hydrator->setUnitOfWork($services->get('UnitOfWork')))
            ->then($iterator = $hydrator->setResult($result)->getIterator())
            ->then($bouh = $iterator->current())
            ->object($bouh)
                ->isInstanceOf('tests\fixtures\model\Bouh')
            ->string($bouh->getName())
                ->isIdenticalTo('Robez-Masson')
            ->string($bouh->getFirstname())
                ->isIdenticalTo('Sylvain');
    }


    public function testCountShouldReturn2()
    {
        $result = new \mock\CCMBenchmark\Ting\Driver\Mysqli\Result();

        $this->calling($result)->getNumRows = 2;

        $this
            ->if($hydrator = new \CCMBenchmark\Ting\Repository\HydratorSingleObject())
            ->then($hydrator->setResult($result))
            ->integer(count($hydrator))
            ->isIdenticalTo(2);
    }
}
