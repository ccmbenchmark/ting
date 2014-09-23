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

namespace CCMBenchmark\Ting;

use Pimple\Container;

class Services implements ContainerInterface
{

    protected $container = null;

    public function __construct()
    {
        $this->container = new Container();
        $this->container->offsetSet(
            'ConnectionPool',
            function ($container) {
                return new ConnectionPool();
            }
        );

        $this->container->offsetSet(
            'MetadataRepository',
            function ($container) {
                return new MetadataRepository($this->get('MetadataFactory'));
            }
        );

        $this->container->offsetSet(
            'UnitOfWork',
            function ($container) {
                return new UnitOfWork($this->get('ConnectionPool'), $this->get('MetadataRepository'));
            }
        );

        $this->container->offsetSet(
            'MetadataFactory',
            function ($container) {
                return new Repository\MetadataFactory($this->get('QueryFactory'));
            }
        );

        $this->container->offsetSet(
            'Collection',
            $this->container->factory(function ($container) {
                return new Repository\Collection();
            })
        );

        $this->container->offsetSet(
            'QueryFactory',
            function ($container) {
                return new Query\QueryFactory();
            }
        );

        $this->container->offsetSet(
            'Hydrator',
            function ($container) {
                return new Repository\Hydrator($this->get('MetadataRepository'), $this->get('UnitOfWork'));
            }
        );

        $this->container->offsetSet(
            'RepositoryFactory',
            function () {
                return new Repository\RepositoryFactory(
                    $this->get('ConnectionPool'),
                    $this->get('MetadataRepository'),
                    $this->get('MetadataFactory'),
                    $this->get('Collection'),
                    $this->get('Hydrator'),
                    $this->get('UnitOfWork')
                );
            }
        );
    }

    public function set($id, callable $callable, $factory = false)
    {
        if ($factory === true) {
            $callable = $this->container->factory($callable);
        }

        $this->container->offsetSet($id, $callable);
        return $this;
    }

    public function get($id)
    {
        return $this->container->offsetGet($id);
    }

    public function has($id)
    {
        return $this->container->offsetExists($id);
    }

    public function getWithArguments($id, $params)
    {
        $callback = $this->container->raw($id);
        return $callback($this->container, $params);
    }
}
