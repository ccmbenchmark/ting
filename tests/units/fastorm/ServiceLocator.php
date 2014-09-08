<?php

namespace tests\units\fastorm;

use \mageekguy\atoum;

class ServiceLocator extends atoum
{
    public function testConstructShouldInitAllDependencies()
    {
        $this
            ->if($serviceLocator = new \fastorm\ServiceLocator())
            ->object($serviceLocator->get('ConnectionPool'))
                ->isInstanceOf('\fastorm\ConnectionPoolInterface')
            ->object($serviceLocator->get('MetadataRepository'))
                ->isInstanceOf('\fastorm\Entity\MetadataRepository')
            ->object($serviceLocator->get('UnitOfWork'))
                ->isInstanceOf('\fastorm\UnitOfWork')
            ->object($serviceLocator->get('Metadata'))
                ->isInstanceOf('\fastorm\Entity\Metadata')
            ->object($serviceLocator->get('Collection'))
                ->isInstanceOf('\fastorm\Entity\Collection')
            ->object($serviceLocator->getWithArguments('Query', ['sql' => '']))
                ->isInstanceOf('\fastorm\Query\QueryAbstract')
            ->object($serviceLocator->getWithArguments('PreparedQuery', ['sql' => '']))
                ->isInstanceOf('\fastorm\Query\QueryAbstract')
            ->object($serviceLocator->get('Hydrator'))
                ->isInstanceOf('\fastorm\Entity\Hydrator');
    }

    public function testShouldImplementsContainerInterface()
    {
        $this
            ->object($serviceLocator = new \fastorm\ServiceLocator())
            ->isInstanceOf('\fastorm\ContainerInterface');
    }

    public function testGetCallbackShouldBeSameCallbackUsedWithSet()
    {
        $callback = function ($bouh) {
            return 'Bouh Wow';
        };

        $this
            ->if($serviceLocator = new \fastorm\ServiceLocator())
            ->and($serviceLocator->set('Bouh', $callback))
            ->string($bouh = $serviceLocator->get('Bouh'))
                ->IsIdenticalTo('Bouh Wow');
    }

    public function testGetShouldReturnSameInstance()
    {
        $callback = function ($bouh) {
            return new \stdClass();
        };

        $this
            ->if($serviceLocator = new \fastorm\ServiceLocator())
            ->and($serviceLocator->set('Bouh', $callback))
            ->object($bouh = $serviceLocator->get('Bouh'))
            ->object($bouh2 = $serviceLocator->get('Bouh'))
                ->IsIdenticalTo($bouh);
    }

    public function testGetShouldReturnNewInstance()
    {
        $callback = function ($bouh) {
            return new \stdClass();
        };

        $this
            ->if($serviceLocator = new \fastorm\ServiceLocator())
            ->and($serviceLocator->set('Bouh', $callback, true))
            ->object($bouh = $serviceLocator->get('Bouh'))
            ->object($bouh2 = $serviceLocator->get('Bouh'))
                ->IsNotIdenticalTo($bouh);
    }

    public function testHasShouldReturnTrue()
    {
        $callback = function ($bouh) {
            return 'Bouh Wow';
        };

        $this
            ->if($serviceLocator = new \fastorm\ServiceLocator())
            ->and($serviceLocator->set('Bouh', $callback))
            ->boolean($serviceLocator->has('Bouh'))
                ->IsTrue();
    }
}
