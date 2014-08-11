<?php

namespace tests\units\fastorm;

use \mageekguy\atoum;

class Query extends atoum
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
                $callback($mockStatement, array(), array(), new \fastorm\Entity\Collection());
            };

        $sql = 'SELECT 1 FROM T_BOUH_BOO WHERE BOO_OLD = :old AND BOO_FIRSTNAME = :fname AND BOO_FLOAT = :bim';

        $this
            ->if($query = new \fastorm\Query(
                $sql,
                array('old' => 3, 'name' => 'bouhName', 'bim' => 3.6)
            ))
            ->then($query->execute($mockDriver))
            ->string($outerSql)
                ->isIdenticalTo($sql)
            ->array($outerParams)
                ->isIdenticalTo(array('old' => 3, 'name' => 'bouhName', 'bim' => 3.6));
    }
}
