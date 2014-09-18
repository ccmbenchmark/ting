<?php

namespace tests\units\CCMBenchmark\Ting\Entity;

use \mageekguy\atoum;

class Metadata extends atoum
{
    public function testSetClassShouldRaiseExceptionWhenStartWithSlash()
    {
        $services = new \CCMBenchmark\Ting\Services();
        $this
            ->if($metadata = new \CCMBenchmark\Ting\Entity\Metadata($services->get('QueryFactory')))
            ->exception(function () use ($metadata) {
                    $metadata->setClass('\my\namespace\Bouh');
            })
                ->hasMessage('Class must not start with a \\');
    }

    public function testAddFieldWithInvalidParametersShouldThrowException()
    {
        $services = new \CCMBenchmark\Ting\Services();
        $this
            ->if($metadata = new \CCMBenchmark\Ting\Entity\Metadata($services->get('QueryFactory')))
            ->exception(function () use ($metadata) {
                $metadata->addField(array('fieldName' => 'bouh'));
            })
                ->hasDefaultCode()
                ->hasMessage('Field configuration must have fieldName and columnName properties');
    }

    public function testSetterWithPrimaryKeyShouldThrowExceptionIfPrimaryAlreadySetted()
    {
        $services = new \CCMBenchmark\Ting\Services();
        $this
            ->if($metadata = new \CCMBenchmark\Ting\Entity\Metadata($services->get('QueryFactory')))
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
        $services = new \CCMBenchmark\Ting\Services();
        $this
            ->if($metadata = new \CCMBenchmark\Ting\Entity\Metadata($services->get('QueryFactory')))
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
        $services = new \CCMBenchmark\Ting\Services();
        $this
            ->if($metadata = new \CCMBenchmark\Ting\Entity\Metadata($services->get('QueryFactory')))
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
        $services = new \CCMBenchmark\Ting\Services();
        $this
            ->if($metadata = new \CCMBenchmark\Ting\Entity\Metadata($services->get('QueryFactory')))
            ->then($metadata->setTable('Bouh'))
            ->then($metadata->addField(array('fieldName' => 'Bouh', 'columnName' => 'boo_bouh')))
            ->boolean($metadata->hasColumn('boo_bouh'))
                ->isTrue();
    }

    public function testHasColumnShouldReturnFalse()
    {
        $services = new \CCMBenchmark\Ting\Services();
        $this
            ->if($metadata = new \CCMBenchmark\Ting\Entity\Metadata($services->get('QueryFactory')))
            ->then($metadata->setTable('Bouh'))
            ->then($metadata->addField(array('fieldName' => 'Bouh', 'columnName' => 'BOO_bouh')))
            ->boolean($metadata->hasColumn('boo_no'))
                ->isFalse();
    }

    public function testCreateEntityShouldReturnObject()
    {
        $services = new \CCMBenchmark\Ting\Services();
        $this
            ->if($metadata = new \CCMBenchmark\Ting\Entity\Metadata($services->get('QueryFactory')))
            ->then($metadata->setClass('mock\repository\BouhRepository'))
            ->object($bouh = $metadata->createEntity())
                ->isInstanceOf('\mock\repository\Bouh');
    }

    public function testSetEntityProperty()
    {
        $services = new \CCMBenchmark\Ting\Services();
        $metadata = new \CCMBenchmark\Ting\Entity\Metadata($services->get('QueryFactory'));
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
        $services = new \CCMBenchmark\Ting\Services();
        $metadata = new \CCMBenchmark\Ting\Entity\Metadata($services->get('QueryFactory'));
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

    public function testConnectShouldCallConnectionPoolConnect()
    {
        $services           = new \CCMBenchmark\Ting\Services();
        $mockConnectionPool = new \mock\CCMBenchmark\Ting\ConnectionPool();
        $this->calling($mockConnectionPool)->connect = true;
        $callback = function ($bouh) {
        };

        $this
            ->if($metadata = new \CCMBenchmark\Ting\Entity\Metadata($services->get('QueryFactory')))
            ->then($metadata->setConnection('bouh_connection'))
            ->then($metadata->setDatabase('bouh_database'))
            ->then($metadata->connect($mockConnectionPool, $callback))
            ->mock($mockConnectionPool)
                ->call('connect')
                    ->withIdenticalArguments('bouh_connection', 'bouh_database', $callback)->once();
    }

    public function testGenerateQueryForPrimaryShouldCallCallbackWithQueryObject()
    {
        $services   = new \CCMBenchmark\Ting\Services();
        $mockDriver = new \mock\CCMBenchmark\Ting\Driver\Mysqli\Driver();

        $query = new \CCMBenchmark\Ting\Query\Query([
            'sql'    => 'SELECT `id`, `bo_name` FROM `T_BOUH_BO` WHERE `id` = :primary',
            'params' => ['primary' => 'BOuH']
        ]);

        $this
            ->if($metadata = new \CCMBenchmark\Ting\Entity\Metadata($services->get('QueryFactory')))
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
                ->isCloneOf($query);

    }

    public function testGenerateQueryForInsertShouldCallCallbackWithQueryObject()
    {
        $services   = new \CCMBenchmark\Ting\Services();
        $mockDriver = new \mock\CCMBenchmark\Ting\Driver\Mysqli\Driver();

        $entity = new \tests\fixtures\model\Bouh();
        $entity->setId(123);
        $entity->setFirstname('Sylvain');
        $entity->setName('Robez-Masson');

        $query = new \CCMBenchmark\Ting\Query\PreparedQuery([
            'sql' => 'INSERT INTO `T_BOUH_BO` (`boo_id`, `boo_name`, `boo_firstname`) '
            . 'VALUES (:boo_id, :boo_name, :boo_firstname)',
            'params' => [
                'boo_id'        => 123,
                'boo_firstname' => 'Sylvain',
                'boo_name'      => 'Robez-Masson'
            ]
        ]);

        $this
            ->if($metadata = new \CCMBenchmark\Ting\Entity\Metadata($services->get('QueryFactory')))
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
                ->isCloneOf($query);
    }

    public function testGenerateQueryForUpdateShouldCallCallbackWithQueryObject()
    {
        $services   = new \CCMBenchmark\Ting\Services();
        $mockDriver = new \mock\CCMBenchmark\Ting\Driver\Mysqli\Driver();

        $entity = new \tests\fixtures\model\Bouh();
        $entity->setId(123);
        $entity->setFirstname('Sylvain');
        $entity->setName('Robez-Masson');

        $query = new \CCMBenchmark\Ting\Query\PreparedQuery([
            'sql'    => 'UPDATE `T_BOUH_BO` SET `boo_name` = :boo_name WHERE `boo_id` = :boo_id',
            'params' => ['boo_id' => 123, 'boo_name' => 'Robez-Masson']
        ]);

        $properties = array('name');

        $this
            ->if($metadata = new \CCMBenchmark\Ting\Entity\Metadata($services->get('QueryFactory')))
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
                ->isCloneOf($query);
    }

    public function testGenerateQueryForDeleteShouldCallCallbackWithQueryObject()
    {
        $services   = new \CCMBenchmark\Ting\Services();
        $mockDriver = new \mock\CCMBenchmark\Ting\Driver\Mysqli\Driver();

        $entity = new \tests\fixtures\model\Bouh();
        $entity->setId(123);

        $query = new \CCMBenchmark\Ting\Query\PreparedQuery([
            'sql'    => 'DELETE FROM `T_BOUH_BO` WHERE `boo_id` = :boo_id',
            'params' => ['boo_id' => 123]
        ]);

        $this
            ->if($metadata = new \CCMBenchmark\Ting\Entity\Metadata($services->get('QueryFactory')))
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
                ->isCloneOf($query);
    }
}
