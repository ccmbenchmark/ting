<?php

namespace tests\units\fastorm\Query;

use \mageekguy\atoum;

class PreparedQuery extends atoum
{

    public function testExecuteShouldCallDriverPrepare()
    {
        $mockStatement = new \mock\fastorm\Driver\Mysqli\Statement();
        $mockDriver    = new \mock\fastorm\Driver\Mysqli\Driver();

        $this->calling($mockStatement)->execute =
            function ($mockStatement, $params, $paramsOrder, $collection) use (&$outerParams) {
                $outerParams = $params;
            };

        $this->calling($mockDriver)->prepare =
            function ($sql, $callback) use (&$outerSql, $mockStatement) {
                $outerSql = $sql;
                $callback($mockStatement, array(), array());
            };

        $sql = 'SELECT 1 FROM T_BOUH_BOO WHERE BOO_OLD = :old AND BOO_FIRSTNAME = :fname AND BOO_FLOAT = :bim';

        $this
            ->if($query = new \fastorm\Query\PreparedQuery(
                ['sql' => $sql, 'params' => ['old' => 3, 'name' => 'bouhName', 'bim' => 3.6]]
            ))
            ->then($query->setDriver($mockDriver)->prepare()->execute())
            ->string($outerSql)
                ->isIdenticalTo($sql)
            ->array($outerParams)
                ->isIdenticalTo(array('old' => 3, 'name' => 'bouhName', 'bim' => 3.6));
    }

    public function testExecuteShouldRaiseExceptionIfNotPrepared()
    {
        $sql = 'SELECT 1 FROM T_BOUH_BOO WHERE BOO_OLD = :old AND BOO_FIRSTNAME = :fname AND BOO_FLOAT = :bim';
        $this
            ->if($query = new \fastorm\Query\PreparedQuery(['sql' => $sql]))
            ->exception(function () use ($query) {
                    $query->execute();
            })
            ->isInstanceOf('\fastorm\Query\QueryException')
        ;
    }

    public function testSetParamsShouldReturnPreparedQuery()
    {
        $sql = 'SELECT 1 FROM T_BOUH_BOO WHERE BOO_OLD = :old AND BOO_FIRSTNAME = :fname AND BOO_FLOAT = :bim';
        $this
            ->if($query = new \fastorm\Query\PreparedQuery(['sql' => $sql]))
            ->object($query->setParams(array('old' => 3, 'name' => 'bouhName', 'bim' => 3.6)))
                ->isIdenticalTo($query)
        ;
    }

    public function testSetDriverShouldReturnPreparedQuery()
    {
        $sql = 'SELECT 1 FROM T_BOUH_BOO WHERE BOO_OLD = :old AND BOO_FIRSTNAME = :fname AND BOO_FLOAT = :bim';
        $mockDriver    = new \mock\fastorm\Driver\Mysqli\Driver();
        $this
            ->if($query = new \fastorm\Query\PreparedQuery(['sql' => $sql]))
            ->object($query->setDriver($mockDriver))
            ->isIdenticalTo($query)
        ;
    }

    public function testPrepareShouldRaiseExceptionIfNoDriver()
    {
        $sql = 'SELECT 1 FROM T_BOUH_BOO WHERE BOO_OLD = :old AND BOO_FIRSTNAME = :fname AND BOO_FLOAT = :bim';
        $this
            ->if($query = new \fastorm\Query\PreparedQuery(['sql' => $sql]))
            ->exception(function () use ($query) {
                $query->prepare();
            })
                ->isInstanceOf('\fastorm\Query\QueryException')
            ;
    }

    public function testPrepareShouldReturnSameObjectAtSecondCall()
    {
        $fakeDriver         = new \mock\Fake\Mysqli();
        $mockDriver         = new \mock\fastorm\Driver\Mysqli\Driver($fakeDriver);

        $sql = 'SELECT 1 FROM T_BOUH_BOO WHERE BOO_OLD = :old AND BOO_FIRSTNAME = :fname AND BOO_FLOAT = :bim';
        $this
            ->if($query = new \fastorm\Query\PreparedQuery(['sql' => $sql]))
            ->then($query->setDriver($mockDriver))
            ->object($query->prepare())
                ->isIdenticalTo($query->prepare())
        ;
    }

    public function testExecuteShouldRaiseExceptionIfNoDriver()
    {
        $sql = 'SELECT 1 FROM T_BOUH_BOO WHERE BOO_OLD = :old AND BOO_FIRSTNAME = :fname AND BOO_FLOAT = :bim';
        $this
            ->if($query = new \fastorm\Query\PreparedQuery(['sql' => $sql]))
            ->exception(function () use ($query) {
                    $query->execute();
            })
            ->isInstanceOf('\fastorm\Query\QueryException')
        ;
    }

    public function testExecuteShouldPrepareQueryIfNot()
    {
        $mockStatement = new \mock\fastorm\Driver\Mysqli\Statement();
        $mockDriver    = new \mock\fastorm\Driver\Mysqli\Driver();

        $this->calling($mockStatement)->execute =
            function ($mockStatement, $params, $paramsOrder, $collection) use (&$outerParams) {
                $outerParams = $params;
            };

        $this->calling($mockDriver)->prepare =
            function ($sql, $callback) use (&$outerSql, $mockStatement) {
                $outerSql = $sql;
                $callback($mockStatement, array(), array());
            };

        $sql = 'SELECT 1 FROM T_BOUH_BOO WHERE BOO_OLD = :old AND BOO_FIRSTNAME = :fname AND BOO_FLOAT = :bim';

        $this
            ->if($query = new \fastorm\Query\PreparedQuery(
                ['sql' => $sql, 'params' => ['old' => 3, 'name' => 'bouhName', 'bim' => 3.6]]
            ))
            ->then($query->setDriver($mockDriver)->execute())
            ->string($outerSql)
                ->isIdenticalTo($sql)
            ->array($outerParams)
                ->isIdenticalTo(array('old' => 3, 'name' => 'bouhName', 'bim' => 3.6));
    }
}
