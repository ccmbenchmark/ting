<?php

namespace tests\units\fastorm\Entity;

use \mageekguy\atoum;

class Metadata extends atoum
{
    public function testSetClassShouldRaiseExceptionWhenStartWithSlash()
    {
        $this
            ->if($metadata = new \fastorm\Entity\Metadata())
            ->exception(function () use ($metadata) {
                    $metadata->setClass('\my\namespace\Bouh');
            })
                ->hasMessage('Class must not start with a \\');
    }

    public function testAddFieldWithInvalidParametersShouldThrowException()
    {
        $this
            ->if($metadata = new \fastorm\Entity\Metadata())
            ->exception(function () use ($metadata) {
                $metadata->addField(array('fieldName' => 'bouh'));
            })
                ->hasDefaultCode()
                ->hasMessage('Field configuration must have fieldName and columnName properties');
    }

    public function testSetterWithPrimaryKeyShouldThrowExceptionIfPrimaryAlreadySetted()
    {
        $this
            ->if($metadata = new \fastorm\Entity\Metadata())
            ->then($metadata->addField(array(
                'id' => true,
                'fieldName' => 'bouhField',
                'columnName' => 'bouhColumn'
            )))
            ->exception(function () use ($metadata) {
                $metadata->addField(array(
                    'id'         => true,
                    'fieldName'  => 'bouhSecondField',
                    'columnName' => 'bouhSecondColumn'
                ));
            })
                ->hasDefaultCode()
                ->hasMessage('Primary key has already been setted.');
    }

    public function testIfTableKnownShouldCallCallback()
    {
        $this
            ->if($metadata = new \fastorm\Entity\Metadata())
            ->then($metadata->setTable('Bouh'))
            ->then($metadata->ifTableKnown('bouh', function ($metadata) use (&$outerMetadata) {
                $outerMetadata = $metadata;
            }))
            ->object($outerMetadata)
                ->isIdenticalTo($metadata);
    }

    public function testCreateObjectShouldReturnObject()
    {
        $this
            ->if($metadata = new \fastorm\Entity\Metadata())
            ->then($metadata->setClass('mock\repository\BouhRepository'))
            ->object($bouh = $metadata->createObject())
                ->isInstanceOf('\mock\repository\Bouh');
    }

    public function testSetObjectProperty()
    {
        $metadata = new \fastorm\Entity\Metadata();
        $metadata->setClass('mock\repository\BouhRepository');
        $metadata->addField(array(
            'fieldName'  => 'name',
            'columnName' => 'boo_name'
        ));

        $bouh = $metadata->createObject();
        $this->calling($bouh)->setName = function ($name) {
            $this->name = $name;
        };

        $this
            ->if($metadata->setObjectProperty($bouh, 'boo_name', 'Sylvain'))
            ->string($bouh->name)
                ->isIdenticalTo('Sylvain');
    }

    public function testSetObjectPropertyShouldRaiseException()
    {
        $notBouh = new \mock\repository\NotBouhRepository();

        $this
            ->if($metadata = new \fastorm\Entity\Metadata())
            ->then($metadata->setClass('mock\repository\BouhRepository'))
            ->then($metadata->addField(array(
                'fieldName'  => 'name',
                'columnName' => 'boo_name'
            )))
            ->exception(function () use ($metadata, $notBouh) {
                $metadata->setObjectProperty($notBouh, 'boo_name', 'Sylvain');
            })
                ->hasMessage('setObjectProperty must be called on object of the Metadata\'s repository');
    }

    public function testAddIntoShouldCallMetadataRepositoryAdd()
    {
        $mockMetadataRepository = new \mock\fastorm\Entity\MetadataRepository();

        $this
            ->if($metadata = new \fastorm\Entity\Metadata())
            ->then($metadata->setClass('Bouh'))
            ->then($metadata->addInto($mockMetadataRepository))
            ->mock($mockMetadataRepository)
                ->call('add')
                    ->withIdenticalArguments('Bouh', $metadata)->once();
    }

    public function testConnectShouldCallConnectionPoolConnect()
    {
        $mockConnectionPool = new \mock\fastorm\ConnectionPool();
        $callback = function ($bouh) { };

        $this
            ->if($metadata = new \fastorm\Entity\Metadata())
            ->then($metadata->setConnection('bouh_connection'))
            ->then($metadata->setDatabase('bouh_database'))
            ->then($metadata->connect($mockConnectionPool, $callback))
            ->mock($mockConnectionPool)
                ->call('connect')
                    ->withIdenticalArguments('bouh_connection', 'bouh_database', $callback)->once();
    }

    public function testColumnsShouldCallCallback()
    {
        $mockConnectionPool = new \mock\fastorm\ConnectionPool();
        $callback = function ($bouh) { };

        $this
            ->if($metadata = new \fastorm\Entity\Metadata())
            ->then($metadata->addField(array(
                'fieldName'  => 'name1',
                'columnName' => 'boo_name1'
            )))
            ->then($metadata->addField(array(
                'fieldName'  => 'name2',
                'columnName' => 'boo_name2'
            )))
            ->then($metadata->columns(function ($fields) use (&$outerFields) {
                $outerFields = $fields;
            }))
            ->array($outerFields)
                ->isIdenticalTo(array(
                    'boo_name1' => array(
                        'fieldName'  => 'name1',
                        'columnName' => 'boo_name1'
                    ),
                    'boo_name2' => array(
                        'fieldName'  => 'name2',
                        'columnName' => 'boo_name2'
                    )
                ));
    }
}
