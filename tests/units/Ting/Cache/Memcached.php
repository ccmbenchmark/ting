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


use mageekguy\atoum;

class Memcached extends atoum
{
    protected $memcachedMock;
    protected $memcached;

    public function beforeTestMethod($method)
    {
        $this->memcachedMock = new \mock\Fake\Memcached();
        $this->memcached     = new \CCMBenchmark\Ting\Cache\Memcached();
        $this->memcached->setConfig(['servers' => ['Bouh']]);
        $this->memcached->setConnection($this->memcachedMock);
    }

    public function testGetShouldReturnNullOnNonExistentKey()
    {
        $this->calling($this->memcachedMock)->get = false;
        $this
            ->variable($this->memcached->get('key'))
                ->isNull()
        ;
    }

    public function testGetShouldCallMemcachedGet()
    {
        $this
            ->if($this->memcached->get('key'))
            ->mock($this->memcachedMock)
                ->call('get')
                    ->withArguments('key')
                        ->once()
        ;
    }

    public function testGetMultiShouldReturnNullWhenNoKeyIsPresent()
    {
        $this->calling($this->memcachedMock)->getMulti = false;
        $this
            ->variable($this->memcached->getMulti(['key1', 'key2', 'key3']))
            ->isNull()
        ;
    }

    public function testGetMultiShouldCallMemcachedGetMulti()
    {
        $keys = array('key1', 'key2');
        $this
            ->if($this->memcached->getMulti($keys))
            ->mock($this->memcachedMock)
                ->call('getMulti')
                    ->withArguments($keys)
                        ->once()
        ;
    }

    public function testStoreShouldCallMemcachedSet()
    {
        $key    = 'bouhKey';
        $value  = 'bouhValue';
        $ttl    = 60;
        $this
            ->if($this->memcached->store($key, $value, $ttl))
            ->mock($this->memcachedMock)
                ->call('set')
                    ->withArguments($key, $value, $ttl)
                        ->once()
        ;
    }

    public function testStoreMultiShouldCallMemcachedSetMulti()
    {
        $values = [
            'bouhKey'  => 'bouhValue',
            'bouhKey2' => 'bouhValue2'
        ];
        $ttl  = 60;
        $this
            ->if($this->memcached->storeMulti($values, $ttl))
            ->mock($this->memcachedMock)
                ->call('setMulti')
                    ->withArguments($values, $ttl)
                        ->once()
        ;
    }

    public function testDeleteShouldCallMemcachedDelete()
    {
        $key = 'bouhKey';
        $this
            ->if($this->memcached->delete($key))
            ->mock($this->memcachedMock)
                ->call('delete')
                    ->withArguments($key)
                        ->once()
        ;
    }

    public function testDeleteMultiShouldCallMemcachedDeleteMulti()
    {
        $keys = ['bouhKey1', 'bouhKey2'];
        $this
            ->if($this->memcached->deleteMulti($keys))
            ->mock($this->memcachedMock)
                ->call('deleteMulti')
                    ->withArguments($keys)
                        ->once()
        ;
    }

    public function testReplaceShouldCallMemcachedReplace()
    {
        $key    = 'bouhKey';
        $value  = 'bouhValue';
        $ttl    = 60;
        $this
            ->if($this->memcached->replace($key, $value, $ttl))
            ->mock($this->memcachedMock)
                ->call('replace')
                    ->withArguments($key, $value, $ttl)
                        ->once()
        ;
    }

    public function testConnectWhenAlreadyConnectedShouldDoNothing()
    {
        $this
            ->if($this->memcached->get('Bouh'))
            ->then($this->memcached->get('Bouh'))
            ->mock($this->memcachedMock)
                ->call('addServers')
                    ->once()
        ;
    }

    public function testConnectWithoutConfigShouldRaiseError()
    {
        $this
            ->if($memcached = new \CCMBenchmark\Ting\Cache\Memcached())
            ->exception(function () use ($memcached) {
                $memcached->get('Bouh');
            })
                ->hasMessage('Must setConfig priory to use Memcached')
        ;
    }

    public function testConnectWithoutConfigServersShouldRaiseError()
    {
        $this
            ->if($memcached = new \CCMBenchmark\Ting\Cache\Memcached())
            ->then($memcached->setConfig(['Bouh']))
            ->exception(function () use ($memcached) {
                $memcached->get('Bouh');
            })
                ->hasMessage('Config must have servers to use Memcached')
        ;
    }

    public function testConnectShouldCallMetadataGetServerListResetServerListAndAddServers()
    {
        $this
            ->if($this->memcached->get('Bouh'))
            ->mock($this->memcachedMock)
                ->call('getServerList')
                    ->once()
                ->call('resetServerList')
                    ->once()
                ->call('addServers')
                    ->once()
        ;
    }

    public function testConnectTwiceShouldNotCallMetadataGetServerListResetServerListAndAddServers()
    {
        $this
            ->if($this->memcached->get('Bouh'))
            ->then($this->memcached->get('Bouh'))
            ->mock($this->memcachedMock)
                ->call('getServerList')
                    ->once()
                ->call('resetServerList')
                    ->once()
                ->call('addServers')
                    ->once()
        ;
    }

    public function testConfigWithOptionsShouldCallMemcachedSetOptions()
    {
        $this
            ->if($this->memcached->setConfig([
                'servers' => ['Bouh'],
                'options' => ['Bouh']
            ]))
            ->then($this->memcached->get('Bouh'))
            ->mock($this->memcachedMock)
                ->call('setOptions')
                    ->once()
        ;
    }

    public function testGetPersistentIdShouldReturnConfiguredValue()
    {
        $this
            ->if($this->memcached->setConfig(['persistent_id' => 'Bouh']))
            ->string($this->memcached->getPersistentId())
                ->isIdenticalTo('Bouh')
        ;
    }

    public function testGetPersistentIdShouldReturnNull()
    {
        $this
            ->variable($this->memcached->getPersistentId())
                ->isNull
        ;
    }
}
