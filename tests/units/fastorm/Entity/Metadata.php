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
                'primary'    => true,
                'fieldName'  => 'bouhField',
                'columnName' => 'bouhColumn'
            )))
            ->exception(function () use ($metadata) {
                $metadata->addField(array(
                    'primary'    => true,
                    'fieldName'  => 'bouhSecondField',
                    'columnName' => 'bouhSecondColumn'
                ));
            })
                ->hasDefaultCode()
                ->hasMessage('Primary key has already been setted.');
    }

    public function testIfTableKnownShouldCallCallbackAndReturnTrue()
    {
        $this
            ->if($metadata = new \fastorm\Entity\Metadata())
            ->then($metadata->setTable('Bouh'))
            ->boolean($metadata->ifTableKnown('Bouh', function ($metadata) use (&$outerMetadata) {
                $outerMetadata = $metadata;
            }))
                ->isTrue()
            ->object($outerMetadata)
                ->isIdenticalTo($metadata);
    }

    public function testIfTableKnownShouldReturnFalse()
    {
        $this
            ->if($metadata = new \fastorm\Entity\Metadata())
            ->then($metadata->setTable('Bouh'))
            ->boolean($metadata->ifTableKnown(
                'Bim',
                function () {
                }
            ))
                ->isFalse();
    }

    public function testHasColumnShouldReturnTrue()
    {
        $this
            ->if($metadata = new \fastorm\Entity\Metadata())
            ->then($metadata->setTable('Bouh'))
            ->then($metadata->addField(array('fieldName' => 'Bouh', 'columnName' => 'boo_bouh')))
            ->boolean($metadata->hasColumn('boo_bouh'))
                ->isTrue();
    }

    public function testHasColumnShouldReturnFalse()
    {
        $this
            ->if($metadata = new \fastorm\Entity\Metadata())
            ->then($metadata->setTable('Bouh'))
            ->then($metadata->addField(array('fieldName' => 'Bouh', 'columnName' => 'BOO_bouh')))
            ->boolean($metadata->hasColumn('boo_no'))
                ->isFalse();
    }

    public function testCreateEntityShouldReturnObject()
    {
        $this
            ->if($metadata = new \fastorm\Entity\Metadata())
            ->then($metadata->setClass('mock\repository\BouhRepository'))
            ->object($bouh = $metadata->createEntity())
                ->isInstanceOf('\mock\repository\Bouh');
    }

    public function testSetEntityProperty()
    {
        $metadata = new \fastorm\Entity\Metadata();
        $metadata->setClass('mock\repository\BouhRepository');
        $metadata->addField(array(
            'fieldName'  => 'name',
            'columnName' => 'boo_name'
        ));

        $bouh = $metadata->createEntity();
        $this->calling($bouh)->setName = function ($name) {
            $this->name = $name;
        };

        $this
            ->if($metadata->setEntityProperty($bouh, 'boo_name', 'Sylvain'))
            ->string($bouh->name)
                ->isIdenticalTo('Sylvain');
    }

    public function testSetEntityPrimary()
    {
        $metadata = new \fastorm\Entity\Metadata();
        $metadata->setClass('mock\repository\BouhRepository');
        $metadata->addField(array(
            'primary'    => true,
            'fieldName'  => 'id',
            'columnName' => 'boo_id'
        ));

        $bouh = $metadata->createEntity();
        $this->calling($bouh)->setId = function ($id) {
            $this->id = $id;
        };

        $this
            ->if($metadata->setEntityPrimary($bouh, 321))
            ->integer($bouh->id)
                ->isIdenticalTo(321);
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
        $callback = function ($bouh) {
        };

        $this
            ->if($metadata = new \fastorm\Entity\Metadata())
            ->then($metadata->setConnection('bouh_connection'))
            ->then($metadata->setDatabase('bouh_database'))
            ->then($metadata->connect($mockConnectionPool, $callback))
            ->mock($mockConnectionPool)
                ->call('connect')
                    ->withIdenticalArguments('bouh_connection', 'bouh_database', $callback)->once();
    }

    public function testGenerateQueryForPrimaryShouldCallCallbackWithQueryObject()
    {
        $mockDriver = new \mock\fastorm\Driver\Mysqli\Driver();

        $this
            ->if($metadata = new \fastorm\Entity\Metadata())
            ->then($metadata->setTable('T_BOUH_BO'))
            ->then($metadata->addField(array(
                'primary'    => true,
                'fieldName'  => 'id',
                'columnName' => 'id'
            )))
            ->then($metadata->addField(array(
                'fieldName'  => 'name',
                'columnName' => 'bo_name'
            )))
            ->then($metadata->generateQueryForPrimary($mockDriver, 'BOuH', function ($query) use (&$outerQuery) {
                $outerQuery = $query;
            }))
            ->object($outerQuery)
                ->isCloneOf(
                    new \fastorm\Query(
                        'SELECT `id`, `bo_name` FROM `T_BOUH_BO` WHERE `id` = :primary',
                        array('primary' => 'BOuH')
                    )
                );

    }

    public function testGenerateQueryForInsertShouldCallCallbackWithQueryObject()
    {
        $mockDriver = new \mock\fastorm\Driver\Mysqli\Driver();

        $entity = new \tests\fixtures\model\Bouh();
        $entity->setId(123);
        $entity->setFirstname('Sylvain');
        $entity->setName('Robez-Masson');

        $this
            ->if($metadata = new \fastorm\Entity\Metadata())
            ->then($metadata->setTable('T_BOUH_BO'))
            ->then($metadata->addField(array(
                'primary'    => true,
                'fieldName'  => 'id',
                'columnName' => 'boo_id'
            )))
            ->then($metadata->addField(array(
                'fieldName'  => 'name',
                'columnName' => 'boo_name'
            )))
            ->then($metadata->addField(array(
                'fieldName'  => 'firstname',
                'columnName' => 'boo_firstname'
            )))
            ->then($metadata->generateQueryForInsert($mockDriver, $entity, function ($query) use (&$outerQuery) {
                $outerQuery = $query;
            }))
            ->object($outerQuery)
                ->isCloneOf(
                    new \fastorm\PreparedQuery(
                        'INSERT INTO `T_BOUH_BO` (`boo_id`, `boo_name`, `boo_firstname`) '
                        . 'VALUES (:boo_id, :boo_name, :boo_firstname)',
                        array(
                            'boo_id'        => 123,
                            'boo_firstname' => 'Sylvain',
                            'boo_name'      => 'Robez-Masson'
                        )
                    )
                );
    }

    public function testGenerateQueryForUpdateShouldCallCallbackWithQueryObject()
    {
        $mockDriver = new \mock\fastorm\Driver\Mysqli\Driver();

        $entity = new \tests\fixtures\model\Bouh();
        $entity->setId(123);
        $entity->setFirstname('Sylvain');
        $entity->setName('Robez-Masson');

        $properties = array('name');

        $this
            ->if($metadata = new \fastorm\Entity\Metadata())
            ->then($metadata->setTable('T_BOUH_BO'))
            ->then($metadata->addField(array(
                'primary'    => true,
                'fieldName'  => 'id',
                'columnName' => 'boo_id'
            )))
            ->then($metadata->addField(array(
                'fieldName'  => 'name',
                'columnName' => 'boo_name'
            )))
            ->then($metadata->addField(array(
                'fieldName'  => 'firstname',
                'columnName' => 'boo_firstname'
            )))
            ->then($metadata->generateQueryForUpdate(
                $mockDriver,
                $entity,
                $properties,
                function ($query) use (&$outerQuery) {
                    $outerQuery = $query;
                }
            ))
            ->object($outerQuery)
                ->isCloneOf(
                    new \fastorm\PreparedQuery(
                        'UPDATE `T_BOUH_BO` SET `boo_name` = :boo_name WHERE `boo_id` = :boo_id',
                        array(
                            'boo_id'        => 123,
                            'boo_name'      => 'Robez-Masson'
                        )
                    )
                );
    }

    public function testGenerateQueryForDeleteShouldCallCallbackWithQueryObject()
    {
        $mockDriver = new \mock\fastorm\Driver\Mysqli\Driver();

        $entity = new \tests\fixtures\model\Bouh();
        $entity->setId(123);

        $this
            ->if($metadata = new \fastorm\Entity\Metadata())
            ->then($metadata->setTable('T_BOUH_BO'))
            ->then($metadata->addField(array(
                'primary'    => true,
                'fieldName'  => 'id',
                'columnName' => 'boo_id'
            )))
            ->then($metadata->generateQueryForDelete($mockDriver, $entity, function ($query) use (&$outerQuery) {
                $outerQuery = $query;
            }))
            ->object($outerQuery)
                ->isCloneOf(
                    new \fastorm\PreparedQuery(
                        'DELETE FROM `T_BOUH_BO` WHERE `boo_id` = :boo_id',
                        array(
                            'boo_id'        => 123
                        )
                    )
                );
    }
}
