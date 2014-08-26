<?php

namespace tests\units\fastorm\Entity;

use \mageekguy\atoum;

class Repository extends atoum
{

    public function testInitMetadataShouldRaiseException()
    {
        $this
            ->exception(function () {
                new \fastorm\Entity\Repository();
            })
                ->hasMessage('You should add initMetadata in your class repository');
    }

    public function testExecuteShouldExecuteQuery()
    {
        $mockDriver = new \mock\fastorm\Driver\Mysqli\Driver();
        $mockConnectionPool = new \mock\fastorm\ConnectionPool();
        $this->calling($mockConnectionPool)->connect =
            function ($connectionName, $database, callable $callback) use ($mockDriver) {
                $callback($mockDriver);
            };

        $mockQuery = new \mock\fastorm\Query\SimpleQuery('SELECT * FROM bouh');
        $this->calling($mockQuery)->execute =
            function ($collection) use (&$outerCollection) {
                $outerCollection = $collection;
            };

        $collection = new \fastorm\Entity\Collection();

        $this
            ->if($repository = new \tests\fixtures\model\BouhRepository($mockConnectionPool))
            ->then($repository->execute($mockQuery, $collection))
            ->object($outerCollection)
                ->isIdenticalTo($collection);
    }

    public function testExecuteShouldReturnACollectionIfNoParam()
    {
        $mockDriver = new \mock\fastorm\Driver\Mysqli\Driver();
        $mockConnectionPool = new \mock\fastorm\ConnectionPool();
        $this->calling($mockConnectionPool)->connect =
            function ($connectionName, $database, callable $callback) use ($mockDriver) {
                $callback($mockDriver);
            };

        $mockQuery = new \mock\fastorm\Query\SimpleQuery('SELECT * FROM bouh');
        $this->calling($mockQuery)->execute =
            function ($collection) use (&$outerCollection) {
                $outerCollection = $collection;
            };
        $this
            ->if($repository = new \tests\fixtures\model\BouhRepository($mockConnectionPool))
            ->then($repository->execute($mockQuery))
            ->object($outerCollection)
                ->isInstanceOf('\fastorm\Entity\Collection');
    }

    public function testExecutePreparedShouldPrepareAndExecuteQuery()
    {
        $mockDriver = new \mock\fastorm\Driver\Mysqli\Driver();
        $mockConnectionPool = new \mock\fastorm\ConnectionPool();
        $this->calling($mockConnectionPool)->connect =
            function ($connectionName, $database, callable $callback) use ($mockDriver) {
                $callback($mockDriver);
            };

        $mockQuery = new \mock\fastorm\Query\PreparedQuery('SELECT * FROM bouh WHERE truc = :bidule');
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
            ->if($repository = new \tests\fixtures\model\BouhRepository($mockConnectionPool))
            ->then($repository->executePrepared($mockQuery, $collection))
            ->object($outerCollection)
                ->isIdenticalTo($collection);
    }

    public function testExecutePreparedShouldReturnACollectionIfNoParam()
    {
        $mockDriver = new \mock\fastorm\Driver\Mysqli\Driver();
        $mockConnectionPool = new \mock\fastorm\ConnectionPool();
        $this->calling($mockConnectionPool)->connect =
            function ($connectionName, $database, callable $callback) use ($mockDriver) {
                $callback($mockDriver);
            };

        $mockQuery = new \mock\fastorm\Query\PreparedQuery('SELECT * FROM bouh WHERE truc = :bidule');
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
            ->if($repository = new \tests\fixtures\model\BouhRepository($mockConnectionPool))
            ->then($repository->executePrepared($mockQuery))
            ->object($outerCollection)
                ->isInstanceOf('\fastorm\Entity\Collection');
    }

    public function testGet()
    {
        $mockConnectionPool  = new \mock\fastorm\ConnectionPool();
        $driverFake          = new \mock\Fake\Mysqli();
        $mockDriver          = new \mock\fastorm\Driver\Mysqli\Driver($driverFake);
        $driverStatementFake = new \mock\Fake\DriverStatement();
        $mockMysqliResult    = new \mock\tests\fixtures\FakeDriver\MysqliResult(array());

        $this->calling($driverFake)->prepare = $driverStatementFake;
        $this->calling($driverStatementFake)->get_result = $mockMysqliResult;

        $this->calling($mockMysqliResult)->fetch_fields = function () {
            $fields = array();
            $stdClass = new \stdClass();
            $stdClass->name     = 'id';
            $stdClass->orgname  = 'boo_id';
            $stdClass->table    = 'bouh';
            $stdClass->orgtable = 'T_BOUH_BOO';
            $fields[] = $stdClass;

            $stdClass = new \stdClass();
            $stdClass->name     = 'prenom';
            $stdClass->orgname  = 'boo_firstname';
            $stdClass->table    = 'bouh';
            $stdClass->orgtable = 'T_BOUH_BOO';
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
        $bouh->addPropertyListener(\fastorm\UnitOfWork::getInstance());
        $bouh->setId(3);
        $bouh->setfirstname('Sylvain');

        $this
            ->if($bouhRepository = new \tests\fixtures\model\BouhRepository($mockConnectionPool))
            ->object($bouhRepository->get(3, null, null))
                ->isCloneOf($bouh);
    }

    public function testStartTransactionShouldOpenTransaction()
    {
        $fakeDriver         = new \mock\Fake\Mysqli();
        $mockDriver         = new \mock\fastorm\Driver\Mysqli\Driver($fakeDriver);
        $mockConnectionPool = new \mock\fastorm\ConnectionPool();

        $this->calling($mockConnectionPool)->connect =
            function ($connectionName, $database, callable $callback) use ($mockDriver) {
                $callback($mockDriver);
            };

        $this
            ->if($bouhRepository = new \tests\fixtures\model\BouhRepository($mockConnectionPool))
            ->then($bouhRepository->startTransaction())
            ->boolean($mockDriver->isTransactionOpened())
                ->isTrue();
    }

    public function testCommitShouldCloseTransaction()
    {
        $fakeDriver         = new \mock\Fake\Mysqli();
        $mockDriver         = new \mock\fastorm\Driver\Mysqli\Driver($fakeDriver);
        $mockConnectionPool = new \mock\fastorm\ConnectionPool();

        $this->calling($mockConnectionPool)->connect =
            function ($connectionName, $database, callable $callback) use ($mockDriver) {
                $callback($mockDriver);
            };

        $this
            ->if($bouhRepository = new \tests\fixtures\model\BouhRepository($mockConnectionPool))
            ->then($bouhRepository->startTransaction())
            ->then($bouhRepository->commit())
            ->boolean($mockDriver->isTransactionOpened())
                ->isFalse()
        ;
    }

    public function testRollbackShouldCloseTransaction()
    {
        $fakeDriver         = new \mock\Fake\Mysqli();
        $mockDriver         = new \mock\fastorm\Driver\Mysqli\Driver($fakeDriver);
        $mockConnectionPool = new \mock\fastorm\ConnectionPool();

        $this->calling($mockConnectionPool)->connect =
            function ($connectionName, $database, callable $callback) use ($mockDriver) {
                $callback($mockDriver);
            };

        $this
            ->if($bouhRepository = new \tests\fixtures\model\BouhRepository($mockConnectionPool))
            ->then($bouhRepository->startTransaction())
            ->then($bouhRepository->rollback())
            ->boolean($mockDriver->isTransactionOpened())
                ->isFalse()
        ;
    }
}
