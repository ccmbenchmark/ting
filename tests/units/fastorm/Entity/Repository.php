<?php

namespace tests\units\fastorm\Entity;

use \mageekguy\atoum;

class Repository extends atoum
{
    public function testExecuteShouldExecuteQuery()
    {
        $mockDriver         = new \mock\fastorm\Driver\Mysqli\Driver();
        $mockConnectionPool = new \mock\fastorm\ConnectionPool();
        $this->calling($mockConnectionPool)->connect =
            function ($connectionName, $database, callable $callback) use ($mockDriver) {
                $callback($mockDriver);
            };

        $services  = new \fastorm\Services();
        $mockQuery = new \mock\fastorm\Query\Query(['sql' => 'SELECT * FROM bouh']);
        $this->calling($mockQuery)->execute =
            function ($collection) use (&$outerCollection) {
                $outerCollection = $collection;
            };

        $collection = new \fastorm\Entity\Collection();

        $this
            ->if($repository = new \tests\fixtures\model\BouhRepository(
                $mockConnectionPool,
                $services->get('MetadataRepository'),
                $services->get('MetadataFactory'),
                $services->get('Collection'),
                $services->get('Hydrator')
            ))
            ->then($repository->execute($mockQuery, $collection))
            ->object($outerCollection)
                ->isIdenticalTo($collection);
    }

    public function testExecuteShouldReturnACollectionIfNoParam()
    {
        $services           = new \fastorm\Services();
        $mockDriver         = new \mock\fastorm\Driver\Mysqli\Driver();
        $mockConnectionPool = new \mock\fastorm\ConnectionPool();

        $this->calling($mockConnectionPool)->connect =
            function ($connectionName, $database, callable $callback) use ($mockDriver) {
                $callback($mockDriver);
            };

        $mockQuery = new \mock\fastorm\Query\Query(['sql' => 'SELECT * FROM bouh']);
        $this->calling($mockQuery)->execute =
            function ($collection) use (&$outerCollection) {
                $outerCollection = $collection;
            };
        $this
            ->if($repository = new \tests\fixtures\model\BouhRepository(
                $mockConnectionPool,
                $services->get('MetadataRepository'),
                $services->get('MetadataFactory'),
                $services->get('Collection'),
                $services->get('Hydrator')
            ))
            ->then($repository->execute($mockQuery))
            ->object($outerCollection)
                ->isInstanceOf('\fastorm\Entity\Collection');
    }

    public function testExecutePreparedShouldPrepareAndExecuteQuery()
    {
        $services           = new \fastorm\Services();
        $mockDriver         = new \mock\fastorm\Driver\Mysqli\Driver();
        $mockConnectionPool = new \mock\fastorm\ConnectionPool();

        $this->calling($mockConnectionPool)->connect =
            function ($connectionName, $database, callable $callback) use ($mockDriver) {
                $callback($mockDriver);
            };

        $mockQuery = new \mock\fastorm\Query\PreparedQuery(['sql' => 'SELECT * FROM bouh WHERE truc = :bidule']);
        $this->calling($mockQuery)->prepare =
            function () use ($mockQuery) {
                return $mockQuery;
            }
        ;
        $this->calling($mockQuery)->execute =
            function ($collection) use (&$outerCollection) {
                $outerCollection = $collection;
            };

        $collection = new \fastorm\Entity\Collection();

        $this
            ->if($repository = new \tests\fixtures\model\BouhRepository(
                $mockConnectionPool,
                $services->get('MetadataRepository'),
                $services->get('MetadataFactory'),
                $services->get('Collection'),
                $services->get('Hydrator')
            ))
            ->then($repository->executePrepared($mockQuery, $collection))
            ->object($outerCollection)
                ->isIdenticalTo($collection);
    }

    public function testExecutePreparedShouldReturnACollectionIfNoParam()
    {
        $services           = new \fastorm\Services();
        $mockDriver         = new \mock\fastorm\Driver\Mysqli\Driver();
        $mockConnectionPool = new \mock\fastorm\ConnectionPool();

        $this->calling($mockConnectionPool)->connect =
            function ($connectionName, $database, callable $callback) use ($mockDriver) {
                $callback($mockDriver);
            };

        $mockQuery = new \mock\fastorm\Query\PreparedQuery(['sql' => 'SELECT * FROM bouh WHERE truc = :bidule']);
        $this->calling($mockQuery)->prepare =
            function () use ($mockQuery) {
                return $mockQuery;
            }
        ;
        $this->calling($mockQuery)->execute =
            function ($collection) use (&$outerCollection) {
                $outerCollection = $collection;
            };
        $this
            ->if($repository = new \tests\fixtures\model\BouhRepository(
                $mockConnectionPool,
                $services->get('MetadataRepository'),
                $services->get('MetadataFactory'),
                $services->get('Collection'),
                $services->get('Hydrator')
            ))
            ->then($repository->executePrepared($mockQuery))
            ->object($outerCollection)
                ->isInstanceOf('\fastorm\Entity\Collection');
    }

    public function testGet()
    {
        $services           = new \fastorm\Services();
        $mockConnectionPool = new \mock\fastorm\ConnectionPool();
        $driverFake         = new \mock\Fake\Mysqli();
        $mockDriver         = new \mock\fastorm\Driver\Mysqli\Driver($driverFake);
        $mockMysqliResult   = new \mock\tests\fixtures\FakeDriver\MysqliResult(array());

        $this->calling($driverFake)->query = $mockMysqliResult;

        $this->calling($mockMysqliResult)->fetch_fields = function () {
            $fields = array();
            $stdClass = new \stdClass();
            $stdClass->name     = 'id';
            $stdClass->orgname  = 'boo_id';
            $stdClass->table    = 'bouh';
            $stdClass->orgtable = 'T_BOUH_BOO';
            $stdClass->type     = MYSQLI_TYPE_VAR_STRING;
            $fields[] = $stdClass;

            $stdClass = new \stdClass();
            $stdClass->name     = 'prenom';
            $stdClass->orgname  = 'boo_firstname';
            $stdClass->table    = 'bouh';
            $stdClass->orgtable = 'T_BOUH_BOO';
            $stdClass->type     = MYSQLI_TYPE_VAR_STRING;
            $fields[] = $stdClass;

            return $fields;
        };

        $this->calling($mockMysqliResult)->fetch_array = function ($type) {
            return array(3, 'Sylvain');
        };

        $this->calling($mockConnectionPool)->connect =
            function ($connectionName, $database, callable $callback) use ($mockDriver) {
                $callback($mockDriver);
            };

        $bouh = new \tests\fixtures\model\Bouh();
        $bouh->setId(3);
        $bouh->setfirstname('Sylvain');

        $this
            ->if($bouhRepository = new \tests\fixtures\model\BouhRepository(
                $mockConnectionPool,
                $services->get('MetadataRepository'),
                $services->get('MetadataFactory'),
                $services->get('Collection'),
                $services->get('Hydrator')
            ))
            ->and($testBouh = $bouhRepository->get(3))
            ->integer($testBouh->getId())
                ->isIdenticalTo($bouh->getId())
            ->string($testBouh->getFirstname())
                ->isIdenticalTo($bouh->getFirstname());
    }

    public function testStartTransactionShouldOpenTransaction()
    {
        $services           = new \fastorm\Services();
        $fakeDriver         = new \mock\Fake\Mysqli();
        $mockDriver         = new \mock\fastorm\Driver\Mysqli\Driver($fakeDriver);
        $mockConnectionPool = new \mock\fastorm\ConnectionPool();

        $services->set('ConnectionPool', function ($container) use ($mockConnectionPool) {
            return $mockConnectionPool;
        });

        $this->calling($mockConnectionPool)->connect =
            function ($connectionName, $database, callable $callback) use ($mockDriver) {
                $callback($mockDriver);
            };

        $this
            ->if($bouhRepository = new \tests\fixtures\model\BouhRepository(
                $mockConnectionPool,
                $services->get('MetadataRepository'),
                $services->get('MetadataFactory'),
                $services->get('Collection'),
                $services->get('Hydrator')
            ))
            ->then($bouhRepository->startTransaction())
            ->boolean($mockDriver->isTransactionOpened())
                ->isTrue();
    }

    public function testCommitShouldCloseTransaction()
    {
        $services           = new \fastorm\Services();
        $fakeDriver         = new \mock\Fake\Mysqli();
        $mockDriver         = new \mock\fastorm\Driver\Mysqli\Driver($fakeDriver);
        $mockConnectionPool = new \mock\fastorm\ConnectionPool();

        $this->calling($mockConnectionPool)->connect =
            function ($connectionName, $database, callable $callback) use ($mockDriver) {
                $callback($mockDriver);
            };

        $this
            ->if($bouhRepository = new \tests\fixtures\model\BouhRepository(
                $mockConnectionPool,
                $services->get('MetadataRepository'),
                $services->get('MetadataFactory'),
                $services->get('Collection'),
                $services->get('Hydrator')
            ))
            ->then($bouhRepository->startTransaction())
            ->then($bouhRepository->commit())
            ->boolean($mockDriver->isTransactionOpened())
                ->isFalse()
        ;
    }

    public function testRollbackShouldCloseTransaction()
    {
        $services           = new \fastorm\Services();
        $fakeDriver         = new \mock\Fake\Mysqli();
        $mockDriver         = new \mock\fastorm\Driver\Mysqli\Driver($fakeDriver);
        $mockConnectionPool = new \mock\fastorm\ConnectionPool();

        $this->calling($mockConnectionPool)->connect =
            function ($connectionName, $database, callable $callback) use ($mockDriver) {
                $callback($mockDriver);
            };

        $this
            ->if($bouhRepository = new \tests\fixtures\model\BouhRepository(
                $mockConnectionPool,
                $services->get('MetadataRepository'),
                $services->get('MetadataFactory'),
                $services->get('Collection'),
                $services->get('Hydrator')
            ))
            ->then($bouhRepository->startTransaction())
            ->then($bouhRepository->rollback())
            ->boolean($mockDriver->isTransactionOpened())
                ->isFalse()
        ;
    }
}
