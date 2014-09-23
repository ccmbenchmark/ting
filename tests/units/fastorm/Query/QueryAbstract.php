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

use CCMBenchmark\Ting\ConnectionPoolInterface;
use mageekguy\atoum;

class QueryAbstract extends atoum
{
    public function testExecuteCallbackWithConnectionTypeShouldCallCallbackWithCorrectParameter()
    {
        $this
            ->if(
                $callback = function ($connectionParameter) use (&$outerConnectionParameter) {
                    $outerConnectionParameter = $connectionParameter;
                }
            )
            ->and($query = new \tests\fixtures\FakeQuery\FakeQuery(
                'UPDATE "table" SET "name" = :name',
                ['name' => 'value']
            ))
                ->then($query->executeCallbackWithConnectionType($callback))
                ->integer($outerConnectionParameter)
                    ->isIdenticalTo(ConnectionPoolInterface::CONNECTION_MASTER)
            ->and($query = new \tests\fixtures\FakeQuery\FakeQuery(
                'INSERT INTO "table" ("name") VALUES (:name)',
                ['name' => 'value']
            ))
                ->then($query->executeCallbackWithConnectionType($callback))
                ->integer($outerConnectionParameter)
                    ->isIdenticalTo(ConnectionPoolInterface::CONNECTION_MASTER)
            ->and($query = new \tests\fixtures\FakeQuery\FakeQuery(
                'SELECT * FROM "table"'
            ))
                ->then($query->executeCallbackWithConnectionType($callback))
                ->integer($outerConnectionParameter)
                    ->isIdenticalTo(ConnectionPoolInterface::CONNECTION_SLAVE)
        ;
    }

}
