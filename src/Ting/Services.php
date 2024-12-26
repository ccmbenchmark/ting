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

    protected Container|null $container = null;

    /** @var array<string, array<mixed, mixed>>|null  */
    protected array|null $serviceOptions = null;

    public function __construct()
    {
        $this->container = new Container();
        $this->container->offsetSet(
            'ConnectionPool',
            function () {
                return new ConnectionPool();
            }
        );

        $this->container->offsetSet(
            'MetadataRepository',
            function () {
                return new MetadataRepository($this->get('SerializerFactory'));
            }
        );

        $this->container->offsetSet(
            'UnitOfWork',
            function () {
                return new UnitOfWork(
                    $this->get('ConnectionPool'),
                    $this->get('MetadataRepository'),
                    $this->get('QueryFactory')
                );
            }
        );

        $this->container->offsetSet(
            'CollectionFactory',
            $this->container->factory(function () {
                return new Repository\CollectionFactory(
                    $this->get('MetadataRepository'),
                    $this->get('UnitOfWork'),
                    $this->get('Hydrator')
                );
            })
        );

        $this->container->offsetSet(
            'QueryFactory',
            function () {
                return new Query\QueryFactory();
            }
        );

        $this->container->offsetSet(
            'SerializerFactory',
            function () {
                return new Serializer\SerializerFactory();
            }
        );

        $this->container->offsetSet(
            'Hydrator',
            $this->container->factory(function () {
                $hydrator = new Repository\Hydrator();
                $hydrator->setMetadataRepository($this->get('MetadataRepository'));
                $hydrator->setUnitOfWork($this->get('UnitOfWork'));
                return $hydrator;
            })
        );

        $this->container->offsetSet(
            'HydratorSingleObject',
            $this->container->factory(function () {
                $hydrator = new Repository\HydratorSingleObject();
                $hydrator->setMetadataRepository($this->get('MetadataRepository'));
                $hydrator->setUnitOfWork($this->get('UnitOfWork'));
                return $hydrator;
            })
        );

        $this->container->offsetSet(
            'HydratorAggregator',
            $this->container->factory(function () {
                $hydrator = new Repository\HydratorAggregator();
                $hydrator->setMetadataRepository($this->get('MetadataRepository'));
                $hydrator->setUnitOfWork($this->get('UnitOfWork'));
                return $hydrator;
            })
        );

        $this->container->offsetSet(
            'HydratorRelational',
            $this->container->factory(function () {
                $hydrator = new Repository\HydratorRelational();
                $hydrator->setMetadataRepository($this->get('MetadataRepository'));
                $hydrator->setUnitOfWork($this->get('UnitOfWork'));
                return $hydrator;
            })
        );

        $this->container->offsetSet(
            'RepositoryFactory',
            function () {
                return new Repository\RepositoryFactory(
                    $this->get('ConnectionPool'),
                    $this->get('MetadataRepository'),
                    $this->get('QueryFactory'),
                    $this->get('CollectionFactory'),
                    $this->get('UnitOfWork'),
                    $this->get('Cache'),
                    $this->get('SerializerFactory')
                );
            }
        );

        $this->container->offsetSet(
            'Cache',
            function () {
                return new Cache\Cache();
            }
        );
    }

    /**
     * @param mixed    $id
     * @param \Closure $callable
     * @param  bool    $factory
     * @return $this
     */
    public function set(mixed $id, \Closure $callable, $factory = false): self
    {
        if ($factory === true) {
            $callable = $this->container->factory($callable);
        }

        $this->container->offsetSet($id, $callable);
        return $this;
    }

    /**
     * @param mixed                    $id
     * @param array<mixed, mixed>|null $options
     * @return mixed
     */
    public function get(mixed $id, array $options = null): mixed
    {
        if ($options !== null) {
            if (isset($this->serviceOptions[$id]) && $this->serviceOptions[$id] !== $options) {
                throw new \RuntimeException(sprintf('Cannot call service %s with another configuration', $id));
            }
            $this->serviceOptions[$id] = $options;
        }

        return $this->container->offsetGet($id);
    }

    public function has(mixed $id): bool
    {
        return $this->container->offsetExists($id);
    }
}
