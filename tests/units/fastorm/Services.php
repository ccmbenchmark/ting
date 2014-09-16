<?php

namespace tests\units\fastorm;

use \mageekguy\atoum;

class Services extends atoum
{
    public function testConstructShouldInitAllDependencies()
    {
        $this
            ->if($services = new \fastorm\Services())
            ->object($services->get('ConnectionPool'))
                ->isInstanceOf('\fastorm\ConnectionPoolInterface')
            ->object($services->get('MetadataRepository'))
                ->isInstanceOf('\fastorm\Entity\MetadataRepository')
            ->object($services->get('UnitOfWork'))
                ->isInstanceOf('\fastorm\UnitOfWork')
            ->object($services->get('MetadataFactory'))
                ->isInstanceOf('\fastorm\Entity\MetadataFactoryInterface')
            ->object($services->get('Collection'))
                ->isInstanceOf('\fastorm\Entity\Collection')
            ->object($services->get('QueryFactory'))
                ->isInstanceOf('\fastorm\Query\QueryFactoryInterface')
            ->object($services->get('Hydrator'))
                ->isInstanceOf('\fastorm\Entity\Hydrator');
    }

    public function testShouldImplementsContainerInterface()
    {
        $this
            ->object($services = new \fastorm\Services())
            ->isInstanceOf('\fastorm\ContainerInterface');
    }

    public function testGetCallbackShouldBeSameCallbackUsedWithSet()
    {
        $callback = function ($bouh) {
            return 'Bouh Wow';
        };

        $this
            ->if($services = new \fastorm\Services())
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
            ->if($services = new \fastorm\Services())
            ->and($services->set('Bouh', $callback))
            ->object($bouh = $services->get('Bouh'))
            ->object($bouh2 = $services->get('Bouh'))
                ->IsIdenticalTo($bouh);
    }

    public function testGetWithArgumentsShouldConstructObjectWithArguments()
    {
        $callback = function ($bouh, $arguments) use (&$outerArguments) {
            $outerArguments = $arguments;
        };

        $arguments = ['name' => 'Bouh'];

        $this
            ->if($services = new \fastorm\Services())
            ->and($services->set('Bouh', $callback))
            ->and($services->getWithArguments('Bouh', $arguments))
            ->array($arguments)
                ->IsIdenticalTo($outerArguments);
    }

    public function testGetShouldReturnNewInstance()
    {
        $callback = function ($bouh) {
            return new \stdClass();
        };

        $this
            ->if($services = new \fastorm\Services())
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
            ->if($services = new \fastorm\Services())
            ->and($services->set('Bouh', $callback))
            ->boolean($services->has('Bouh'))
                ->IsTrue();
    }
}
