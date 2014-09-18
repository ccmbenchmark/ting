<?php

namespace CCMBenchmark\Ting;

class Services implements ContainerInterface
{

    protected $container = null;

    public function __construct()
    {
        $this->container = new \Pimple\Container();
        $this->container->offsetSet(
            'ConnectionPool',
            function ($container) {
                return new ConnectionPool();
            }
        );

        $this->container->offsetSet(
            'MetadataRepository',
            function ($container) {
                return new Entity\MetadataRepository($this->get('MetadataFactory'));
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
                return new Entity\MetadataFactory($this->get('QueryFactory'));
            }
        );

        $this->container->offsetSet(
            'Collection',
            $this->container->factory(function ($container) {
                return new Entity\Collection();
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
                return new Entity\Hydrator($this->get('MetadataRepository'), $this->get('UnitOfWork'));
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
