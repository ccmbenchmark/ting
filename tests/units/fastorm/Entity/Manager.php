<?php

namespace tests\units\fastorm\Entity;

use \mageekguy\atoum;

class Manager extends atoum
{

    public function testSingletonExceptionNoConfiguration()
    {
        $this
            ->exception(function () {
                \fastorm\Entity\Manager::getInstance();
            })
                ->hasDefaultCode()
                ->hasMessage('First call to EntityManager must pass configuration in parameters');
    }

    public function testSingleton()
    {
        $this
            ->object(\fastorm\Entity\Manager::getInstance(array('connections' => 'bouh')))
                ->isIdenticalTo(\fastorm\Entity\Manager::getInstance());
    }

    public function testGetClassWithInvalidTableParameter()
    {
        $this
            ->if($object = \fastorm\Entity\Manager::getInstance(array('connections' => 'bouh')))
            ->variable($object->getClass('bouh'))
                ->isNull();
    }

    public function testGetClass()
    {
        $this
            ->if($object = \fastorm\Entity\Manager::getInstance(array('connections' => 'bouh')))
            ->then($object->setTableToClass('bouh_table', 'Bouh'))
            ->variable($object->getClass('bouh_table'))
                ->isIdenticalTo('Bouh');
    }

    public function testLoadMetadata()
    {
        $this
            ->if($object = \fastorm\Entity\Manager::getInstance(array('connections' => 'bouh')))
            ->object($metadata = $object->loadMetaData('\tests\fixtures\model\BouhRepository'))
                ->isInstanceOf('\fastorm\Entity\Metadata');
    }

    public function testLoadMetadataSingleton()
    {
        $this
            ->if($object = \fastorm\Entity\Manager::getInstance(array('connections' => 'bouh')))
            ->object($metadata = $object->loadMetaData('\tests\fixtures\model\BouhRepository'))
                ->isIdenticalTo($object->loadMetaData('\tests\fixtures\model\BouhRepository'));
    }
}
