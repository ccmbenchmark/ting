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

use CCMBenchmark\Ting\Repository\CollectionFactory;
use CCMBenchmark\Ting\Query\QueryFactory;
use CCMBenchmark\Ting\Serializer\SerializerFactory;
use CCMBenchmark\Ting\Repository\Hydrator;
use CCMBenchmark\Ting\Repository\HydratorSingleObject;
use CCMBenchmark\Ting\Repository\HydratorAggregator;
use CCMBenchmark\Ting\Repository\HydratorRelational;
use CCMBenchmark\Ting\Repository\RepositoryFactory;
use CCMBenchmark\Ting\Cache\Cache;
use Closure;
use RuntimeException;
use CCMBenchmark\Ting\Util\PropertyAccessor;
use Pimple\Container;

class Services implements ContainerInterface
{
    protected Container $container;

    protected array $serviceOptions = [];

    public function __construct()
    {
        if (!\class_exists(Container::class)) {
            throw new \RuntimeException('pimple/pimple is required to use Ting services, please run "composer require pimple/pimple" to install it');
        }
        $this->container = new Container();
        $this->container->offsetSet(
            'ConnectionPool',
            fn (): ConnectionPool => new ConnectionPool()
        );

        $this->container->offsetSet(
            'MetadataRepository',
            fn (): MetadataRepository => new MetadataRepository($this->get('SerializerFactory'))
        );

        $this->container->offsetSet(
            'UnitOfWork',
            fn (): UnitOfWork => new UnitOfWork(
                $this->get('ConnectionPool'),
                $this->get('MetadataRepository'),
                $this->get('QueryFactory')
            )
        );

        $this->container->offsetSet(
            'CollectionFactory',
            $this->container->factory(fn (): CollectionFactory => new CollectionFactory(
                $this->get('MetadataRepository'),
                $this->get('UnitOfWork'),
                $this->get('Hydrator')
            ))
        );

        $this->container->offsetSet(
            'QueryFactory',
            fn (): QueryFactory => new QueryFactory()
        );

        $this->container->offsetSet(
            'SerializerFactory',
            fn (): SerializerFactory => new SerializerFactory()
        );

        $this->container->offsetSet(
            'Hydrator',
            $this->container->factory(function (): Hydrator {
                $hydrator = new Hydrator();
                $hydrator->setMetadataRepository($this->get('MetadataRepository'));
                $hydrator->setUnitOfWork($this->get('UnitOfWork'));
                return $hydrator;
            })
        );

        $this->container->offsetSet(
            'HydratorSingleObject',
            $this->container->factory(function (): HydratorSingleObject {
                $hydrator = new HydratorSingleObject();
                $hydrator->setMetadataRepository($this->get('MetadataRepository'));
                $hydrator->setUnitOfWork($this->get('UnitOfWork'));
                return $hydrator;
            })
        );

        $this->container->offsetSet(
            'HydratorAggregator',
            $this->container->factory(function (): HydratorAggregator {
                $hydrator = new HydratorAggregator();
                $hydrator->setMetadataRepository($this->get('MetadataRepository'));
                $hydrator->setUnitOfWork($this->get('UnitOfWork'));
                return $hydrator;
            })
        );

        $this->container->offsetSet(
            'HydratorRelational',
            $this->container->factory(function (): HydratorRelational {
                $hydrator = new HydratorRelational();
                $hydrator->setMetadataRepository($this->get('MetadataRepository'));
                $hydrator->setUnitOfWork($this->get('UnitOfWork'));
                return $hydrator;
            })
        );

        $this->container->offsetSet(
            'RepositoryFactory',
            fn (): RepositoryFactory => new RepositoryFactory(
                $this->get('ConnectionPool'),
                $this->get('MetadataRepository'),
                $this->get('QueryFactory'),
                $this->get('CollectionFactory'),
                $this->get('UnitOfWork'),
                $this->get('Cache'),
                $this->get('SerializerFactory')
            )
        );

        $this->container->offsetSet(
            'Cache',
            fn (): Cache => new Cache()
        );

        $this->container->offsetSet(
            'PropertyAccessor',
            fn(): PropertyAccessor => new PropertyAccessor()
        );
    }

    public function set(string $id, Closure $callable, bool $factory = false): static
    {
        if ($factory === true) {
            $callable = $this->container->factory($callable);
        }

        $this->container->offsetSet($id, $callable);
        return $this;
    }

    public function get(string $id, ?array $options = null): mixed
    {
        if ($options !== null) {
            if (isset($this->serviceOptions[$id]) && $this->serviceOptions[$id] !== $options) {
                throw new RuntimeException(sprintf('Cannot call service %s with another configuration', $id));
            }
            $this->serviceOptions[$id] = $options;
        }

        return $this->container->offsetGet($id);
    }

    public function has(string $id): bool
    {
        return $this->container->offsetExists($id);
    }
}
