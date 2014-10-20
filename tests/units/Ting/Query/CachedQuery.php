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

namespace tests\units\CCMBenchmark\Ting\Query;

use mageekguy\atoum;

class CachedQuery extends atoum
{
    public function testExecuteShouldCallCacheGetAndExecuteQuery()
    {

        $mockMemcached = new \mock\tests\units\fixtures\FakeCache\Memcached();
        $this->calling($mockMemcached)->addServers = true;

        $mockDriver = new \mock\CCMBenchmark\Ting\Driver\Mysqli\Driver();
        $this->calling($mockDriver)->execute = true;

        $mockTingMemcached = new \mock\CCMBenchmark\Ting\Cache\Memcached();
        $mockTingMemcached->setConfig(['servers' => ['Bouh']]);
        $mockTingMemcached->setConnection($mockMemcached);

        $this
            ->if($cachedQuery = new \CCMBenchmark\Ting\Query\CachedQuery('SELECT name FROM Bouh'))
            ->and($cachedQuery->setDriver($mockDriver))
            ->and($cachedQuery->setCacheDriver($mockTingMemcached))
            ->then($cachedQuery->execute(new \CCMBenchmark\Ting\Repository\CachedCollection()))
            ->mock($mockTingMemcached)
                ->call('get')
                    ->once()
            ->mock($mockDriver)
                ->call('execute')
                    ->once()
        ;
    }

    public function testExecuteShouldCallCacheGetAndReturnDataFromMemcached()
    {

        $mockMemcached = new \mock\fixtures\FakeCache\Memcached();
        $this->calling($mockMemcached)->addServers = true;

        $mockDriver = new \mock\CCMBenchmark\Ting\Driver\Mysqli\Driver();
        $this->calling($mockDriver)->execute = true;

        $mockTingMemcached = new \mock\CCMBenchmark\Ting\Cache\Memcached();
        $mockTingMemcached->setConfig(['servers' => ['Bouh']]);
        $mockTingMemcached->setConnection($mockMemcached);
        $this->calling($mockTingMemcached)->get = [[['name' => 'key1', 'value' => 'Bouh 1']]];

        $this
            ->if($cachedQuery = new \CCMBenchmark\Ting\Query\CachedQuery('SELECT name FROM Bouh'))
            ->and($cachedQuery->setDriver($mockDriver))
            ->and($cachedQuery->setCacheDriver($mockTingMemcached))
            ->then($collection = $cachedQuery->execute(new \CCMBenchmark\Ting\Repository\CachedCollection()))
            ->mock($mockTingMemcached)
                ->call('get')
                    ->once()
            ->mock($mockDriver)
                ->call('execute')
                    ->never()
            ->array($collection->current())
                ->isIdenticalTo(['key1' => 'Bouh 1'])
        ;
    }
}
