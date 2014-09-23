<?php
/***********************************************************************
 *
 * Ting - PHP Datamapper
 * ==========================================
 *
 * Copyright (C) 2014 CCM Benchmark Group. (http://www.ccmbenchmark.com)
 *
 ***********************************************************************
 *
 * Licensed under the Apache License, Version 2.0 (the "License"); you
 * may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 *
 *     http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or
 * implied. See the License for the specific language governing
 * permissions and limitations under the License.
 *
 **********************************************************************/

namespace tests\units\CCMBenchmark\Ting\Query;

use mageekguy\atoum;

class PreparedQuery extends atoum
{

    public function testExecuteShouldCallDriverPrepare()
    {
        $mockStatement = new \mock\CCMBenchmark\Ting\Driver\Mysqli\Statement();
        $mockDriver    = new \mock\CCMBenchmark\Ting\Driver\Mysqli\Driver();

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
            ->if($query = new \CCMBenchmark\Ting\Query\PreparedQuery(
                $sql,
                ['old' => 3, 'name' => 'bouhName', 'bim' => 3.6]
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
            ->if($query = new \CCMBenchmark\Ting\Query\PreparedQuery($sql))
            ->exception(function () use ($query) {
                    $query->execute();
            })
            ->isInstanceOf('\CCMBenchmark\Ting\Query\QueryException')
        ;
    }

    public function testSetParamsShouldReturnPreparedQuery()
    {
        $sql = 'SELECT 1 FROM T_BOUH_BOO WHERE BOO_OLD = :old AND BOO_FIRSTNAME = :fname AND BOO_FLOAT = :bim';
        $this
            ->if($query = new \CCMBenchmark\Ting\Query\PreparedQuery($sql))
            ->object($query->setParams(array('old' => 3, 'name' => 'bouhName', 'bim' => 3.6)))
                ->isIdenticalTo($query)
        ;
    }

    public function testSetDriverShouldReturnPreparedQuery()
    {
        $sql = 'SELECT 1 FROM T_BOUH_BOO WHERE BOO_OLD = :old AND BOO_FIRSTNAME = :fname AND BOO_FLOAT = :bim';
        $mockDriver    = new \mock\CCMBenchmark\Ting\Driver\Mysqli\Driver();
        $this
            ->if($query = new \CCMBenchmark\Ting\Query\PreparedQuery($sql))
            ->object($query->setDriver($mockDriver))
            ->isIdenticalTo($query)
        ;
    }

    public function testPrepareShouldRaiseExceptionIfNoDriver()
    {
        $sql = 'SELECT 1 FROM T_BOUH_BOO WHERE BOO_OLD = :old AND BOO_FIRSTNAME = :fname AND BOO_FLOAT = :bim';
        $this
            ->if($query = new \CCMBenchmark\Ting\Query\PreparedQuery($sql))
            ->exception(function () use ($query) {
                $query->prepare();
            })
                ->isInstanceOf('\CCMBenchmark\Ting\Query\QueryException')
            ;
    }

    public function testPrepareShouldReturnSameObjectAtSecondCall()
    {
        $fakeDriver         = new \mock\Fake\Mysqli();
        $mockDriver         = new \mock\CCMBenchmark\Ting\Driver\Mysqli\Driver($fakeDriver);

        $sql = 'SELECT 1 FROM T_BOUH_BOO WHERE BOO_OLD = :old AND BOO_FIRSTNAME = :fname AND BOO_FLOAT = :bim';
        $this
            ->if($query = new \CCMBenchmark\Ting\Query\PreparedQuery($sql))
            ->then($query->setDriver($mockDriver))
            ->object($query->prepare())
                ->isIdenticalTo($query->prepare())
        ;
    }

    public function testExecuteShouldRaiseExceptionIfNoDriver()
    {
        $sql = 'SELECT 1 FROM T_BOUH_BOO WHERE BOO_OLD = :old AND BOO_FIRSTNAME = :fname AND BOO_FLOAT = :bim';
        $this

            ->if($query = new \CCMBenchmark\Ting\Query\PreparedQuery($sql))
            ->exception(function () use ($query) {
                    $query->execute();
            })
            ->isInstanceOf('\CCMBenchmark\Ting\Query\QueryException')
        ;
    }

    public function testExecuteShouldPrepareQueryIfNot()
    {
        $mockStatement = new \mock\CCMBenchmark\Ting\Driver\Mysqli\Statement();
        $mockDriver    = new \mock\CCMBenchmark\Ting\Driver\Mysqli\Driver();

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
            ->if($query = new \CCMBenchmark\Ting\Query\PreparedQuery(
                $sql,
                ['old' => 3, 'name' => 'bouhName', 'bim' => 3.6]
            ))
            ->then($query->setDriver($mockDriver)->execute())
            ->string($outerSql)
                ->isIdenticalTo($sql)
            ->array($outerParams)
                ->isIdenticalTo(array('old' => 3, 'name' => 'bouhName', 'bim' => 3.6));
    }
}
