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

class Services extends atoum
{
    public function testConstructShouldInitAllDependencies()
    {
        $this
            ->if($services = new \CCMBenchmark\Ting\Services())
            ->object($services->get('ConnectionPool'))
                ->isInstanceOf('\CCMBenchmark\Ting\ConnectionPoolInterface')
            ->object($services->get('MetadataRepository'))
                ->isInstanceOf('\CCMBenchmark\Ting\MetadataRepository')
            ->object($services->get('UnitOfWork'))
                ->isInstanceOf('\CCMBenchmark\Ting\UnitOfWork')
            ->object($services->get('MetadataFactory'))
                ->isInstanceOf('\CCMBenchmark\Ting\Repository\MetadataFactoryInterface')
            ->object($services->get('CollectionFactory'))
                ->isInstanceOf('\CCMBenchmark\Ting\Repository\CollectionFactory')
            ->object($services->get('QueryFactory'))
                ->isInstanceOf('\CCMBenchmark\Ting\Query\QueryFactoryInterface')
            ->object($services->get('Hydrator'))
                ->isInstanceOf('\CCMBenchmark\Ting\Repository\Hydrator')
            ->object($services->get('RepositoryFactory'))
                ->isInstanceOf('\CCMBenchmark\Ting\Repository\RepositoryFactory');
    }

    public function testShouldImplementsContainerInterface()
    {
        $this
            ->object($services = new \CCMBenchmark\Ting\Services())
            ->isInstanceOf('\CCMBenchmark\Ting\ContainerInterface');
    }

    public function testGetCallbackShouldBeSameCallbackUsedWithSet()
    {
        $callback = function ($bouh) {
            return 'Bouh Wow';
        };

        $this
            ->if($services = new \CCMBenchmark\Ting\Services())
            ->and($services->set('Bouh', $callback))
            ->string($bouh = $services->get('Bouh'))
                ->IsIdenticalTo('Bouh Wow');
    }

    public function testGetShouldReturnSameInstance()
    {
        $callback = function ($bouh) {
            return new \stdClass();
        };

        $this
            ->if($services = new \CCMBenchmark\Ting\Services())
            ->and($services->set('Bouh', $callback))
            ->object($bouh = $services->get('Bouh'))
            ->object($bouh2 = $services->get('Bouh'))
                ->IsIdenticalTo($bouh);
    }

    public function testGetShouldReturnNewInstance()
    {
        $callback = function ($bouh) {
            return new \stdClass();
        };

        $this
            ->if($services = new \CCMBenchmark\Ting\Services())
            ->and($services->set('Bouh', $callback, true))
            ->object($bouh = $services->get('Bouh'))
            ->object($bouh2 = $services->get('Bouh'))
                ->IsNotIdenticalTo($bouh);
    }

    public function testHasShouldReturnTrue()
    {
        $callback = function ($bouh) {
            return 'Bouh Wow';
        };

        $this
            ->if($services = new \CCMBenchmark\Ting\Services())
            ->and($services->set('Bouh', $callback))
            ->boolean($services->has('Bouh'))
                ->IsTrue();
    }
}
