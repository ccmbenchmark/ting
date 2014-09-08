<?php

namespace fastorm;

class ServiceLocator implements ContainerInterface
{

    protected $container = null;

    public function __construct()
    {
        $this->container = new \Pimple\Container();
        $this->container['ConnectionPool'] = function ($container) {
            return new ConnectionPool();
        };

        $this->container['MetadataRepository'] = function ($container) {
            return new Entity\MetadataRepository($this);
        };

        $this->container['UnitOfWork'] = function ($container) {
            return new UnitOfWork($this);
        };

        $this->container['Metadata'] = $this->container->factory(function ($container) {
            return new Entity\Metadata($this);
        });

        $this->container['Collection'] = $this->container->factory(function ($container) {
            return new Entity\Collection();
        });

        $this->container['Query'] = $this->container->factory(function ($container, $args) {
            return new Query\Query($args);
        });

        $this->container['PreparedQuery'] = $this->container->factory(function ($container, $args) {
            return new Query\PreparedQuery($args);
        });

        $this->container['Hydrator'] = $this->container->factory(function ($container) {
            return new Entity\Hydrator($this);
        });
    }

    public function set($id, callable $callable, $factory = false)
    {
        if ($factory === true) {
            $callable = $this->container->factory($callable);
        }

        $this->container[$id] = $callable;
        return $this;
    }

    public function get($id)
    {
        return $this->container[$id];
    }

    public function has($id)
    {
        return isset($this->container[$id]);
    }

    public function getWithArguments($id, $params)
    {
        $callback = $this->container->raw($id);
        return $callback($this->container, $params);
    }
}
