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

namespace tests\units\CCMBenchmark\Ting\Cache;

use CCMBenchmark\Ting\Logger\CacheLoggerInterface;
use Doctrine\Common\Cache\VoidCache;
use mageekguy\atoum;

class Cache extends atoum
{

    public function testDeleteShouldCallLogger()
    {
        $mockLogger = new \mock\CCMBenchmark\Ting\Logger\CacheLoggerInterface();

        $this
            ->if($cache = new \CCMBenchmark\Ting\Cache\Cache())
            ->then($cache->setCache(new VoidCache()))
            ->then($cache->setLogger($mockLogger))
            ->then($cache->delete('bouh'))
            ->mock($mockLogger)
                ->call('startOperation')
                    ->withIdenticalArguments(CacheLoggerInterface::OPERATION_DELETE, 'bouh')
                ->call('stopOperation')
                    ->once()
        ;
    }

    public function testFetchShouldCallLogger()
    {
        $mockLogger = new \mock\CCMBenchmark\Ting\Logger\CacheLoggerInterface();

        $this
            ->if($cache = new \CCMBenchmark\Ting\Cache\Cache())
            ->then($cache->setCache(new VoidCache()))
            ->then($cache->setLogger($mockLogger))
            ->then($cache->fetch('bouh'))
            ->mock($mockLogger)
                ->call('startOperation')
                    ->withIdenticalArguments(CacheLoggerInterface::OPERATION_GET, 'bouh')
                ->call('stopOperation')
                    ->once()
        ;
    }

    public function testContainsShouldCallLogger()
    {
        $mockLogger = new \mock\CCMBenchmark\Ting\Logger\CacheLoggerInterface();

        $this
            ->if($cache = new \CCMBenchmark\Ting\Cache\Cache())
            ->then($cache->setCache(new VoidCache()))
            ->then($cache->setLogger($mockLogger))
            ->then($cache->contains('bouh'))
            ->mock($mockLogger)
                ->call('startOperation')
                    ->withIdenticalArguments(CacheLoggerInterface::OPERATION_EXIST, 'bouh')
                ->call('stopOperation')
                    ->once()
        ;
    }

    public function testSaveShouldCallLogger()
    {
        $mockLogger = new \mock\CCMBenchmark\Ting\Logger\CacheLoggerInterface();

        $this
            ->if($cache = new \CCMBenchmark\Ting\Cache\Cache())
            ->then($cache->setCache(new VoidCache()))
            ->then($cache->setLogger($mockLogger))
            ->then($cache->save('name', 'Sylvain', 33))
            ->mock($mockLogger)
                ->call('startOperation')
                    ->withIdenticalArguments(CacheLoggerInterface::OPERATION_STORE, 'name', 'Sylvain', 33)
                ->call('stopOperation')
                    ->once()
        ;
    }
}
