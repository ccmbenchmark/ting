<?php

namespace tests\units\fastorm;

use \mageekguy\atoum;

class ConnectionManager extends atoum
{

    public function testShouldRaiseExceptionWhenNoConfigurationArgument()
    {
        $this
            ->exception(function () {
                \fastorm\ConnectionManager::getInstance();
            })
                ->hasDefaultCode()
                ->hasMessage('First call to ConnectionManager must pass configuration in parameters');
    }

    public function testShouldBeSingleton()
    {
        $this
            ->object(\fastorm\ConnectionManager::getInstance(array('bouh')))
            ->isIdenticalTo(\fastorm\ConnectionManager::getInstance());
    }
}
