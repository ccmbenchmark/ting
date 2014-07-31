<?php

namespace tests\units\fastorm;

use \mageekguy\atoum;

class ConnectionPool extends atoum
{

    public function testShouldRaiseExceptionWhenNoConfigurationArgument()
    {
        $this
            ->exception(function () {
                \fastorm\ConnectionPool::getInstance();
            })
                ->hasDefaultCode()
                ->hasMessage('First call to ConnectionPool must pass configuration in parameters');
    }

    public function testShouldBeSingleton()
    {
        $this
            ->object(\fastorm\ConnectionPool::getInstance(array('bouh')))
            ->isIdenticalTo(\fastorm\ConnectionPool::getInstance());
    }
}
