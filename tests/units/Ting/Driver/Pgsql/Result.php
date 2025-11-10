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

namespace tests\units\CCMBenchmark\Ting\Driver\Pgsql;

use atoum;
use CCMBenchmark\Ting\Driver\Pgsql\PGMock;

require_once __DIR__ . '/../../../../fixtures/mock_native_pgsql.php';

class Result extends atoum
{
    public function testSetQueryShouldRaiseExceptionOnColumnAsterisk()
    {
        PGMock::override('pg_num_fields', 1);
        PGMock::override('pg_field_table', function ($result, $index) {
            if ($index === 1) {
                return 'table';
            }
            return false;
        });

        PGMock::override('pg_field_name', fn ($result, $index) => match ($index) {
            0 => 't.*',
            default => false,
        });

        $this
            ->if($result = new \CCMBenchmark\Ting\Driver\Pgsql\Result())
            ->then($result->setConnectionName('connectionName'))
            ->then($result->setDatabase('database'))
            ->then($result->setResult('result resource'))
            ->exception(function () use ($result): void {
                $result->setQuery('select t.* from table as t');
            })
                ->hasMessage('Query invalid: usage of asterisk in column definition is forbidden');
    }

    public function testSetQueryShouldNotRaiseExceptionWhenAsteriskIsInACondition()
    {
        PGMock::override('pg_num_fields', 1);
        PGMock::override('pg_field_table', function ($result, $index) {
            if ($index === 1) {
                return 'table';
            }
            return false;
        });

        PGMock::override('pg_field_name', fn ($result, $index) => match ($index) {
            0 => 't.*',
            default => false,
        });

        $this
            ->if($result = new \CCMBenchmark\Ting\Driver\Pgsql\Result('result resource'))
            ->variable(
                $result->setQuery(
                    'select t.tata CASE WHEN COALESCE(t_avis.note,0) > -5
                    THEN (length(t_avis.en_bref) > 200)::integer*100 ELSE 0 END +
                    COALESCE(t_avis.note,0) as my_note_avis from table as t'
                )
            )
            ->isNull();
    }

    public function testSetQueryShouldRaiseExceptionParseColumns()
    {
        PGMock::override('pg_num_fields', 0);

        $this
            ->if($result = new \CCMBenchmark\Ting\Driver\Pgsql\Result())
            ->then($result->setConnectionName('connectionName'))
            ->then($result->setDatabase('database'))
            ->then($result->setResult('result resource'))
            ->exception(function () use ($result): void {
                $result->setQuery('selectcolumn from table');
            })
                ->hasMessage('Query invalid: can\'t parse columns');
    }

    public function testSetQueryShouldNotRaiseExceptionWhenThereIsNoFromInTheQuery()
    {
        PGMock::override('pg_num_fields', 0);
        PGMock::override('pg_field_table', 'table');

        $this
            ->if($result = new \CCMBenchmark\Ting\Driver\Pgsql\Result())
            ->then($result->setConnectionName('connectionName'))
            ->then($result->setDatabase('database'))
            ->then($result->setResult('result resource'))
            ->variable($result->setQuery('select NOW(1)'))
                ->isNull();
    }

    public function testIterator()
    {
        PGMock::override('pg_result_seek', true);
        PGMock::override('pg_fetch_array', []);

        $this
            ->if($result = new \mock\CCMBenchmark\Ting\Driver\Pgsql\Result())
            ->then($result->setConnectionName('connectionName'))
            ->then($result->setDatabase('database'))
            ->then($result->setResult('result resource'))
            ->then($result->rewind())
            ->mock($result)
                ->call('next')->once()
            ->then($result->key())
            ->mock($result)
                ->call('key')->once()
            ->then($result->next())
            ->mock($result)
                ->call('next')->twice()
            ->then($result->valid())
            ->mock($result)
                ->call('valid')->once()
            ->then($result->current())
            ->mock($result)
                ->call('current')->once();
    }

    public function testIteratorValidShouldReturnFalse()
    {
        PGMock::override('pg_result_seek', true);
        PGMock::override('pg_fetch_array', false);

        $this
            ->if($result = new \CCMBenchmark\Ting\Driver\Pgsql\Result())
            ->then($result->setConnectionName('connectionName'))
            ->then($result->setDatabase('database'))
            ->then($result->setResult('result resource'))
            ->then($result->rewind())
            ->then($result->next())
            ->boolean($result->valid())
                ->isFalse();
    }

    public function testGetNumRows()
    {
        $mockPgsqlResult = new \mock\CCMBenchmark\Ting\Driver\ResultInterface();

        PGMock::override('pg_num_rows', 10);

        $this
            ->if($result = new \CCMBenchmark\Ting\Driver\Pgsql\Result($mockPgsqlResult))
            ->then($result->setResult($mockPgsqlResult))
            ->variable($result->getNumRows())
                ->isEqualTo(10);
    }

    public function testSetQueryTakesFullConditionAsColumn()
    {
        $mockPgsqlResult = new \mock\CCMBenchmark\Ting\Driver\ResultInterface();

        PGMock::override('pg_fetch_array', [1, 1, 2, 3, 6, 7, 8]);

        PGMock::override('pg_field_table', fn ($result, $index) => 'table');

        $this
            ->if($result = new \CCMBenchmark\Ting\Driver\Pgsql\Result($mockPgsqlResult))
            ->and(
                $result->setQuery(
                    'SELECT a,
                            CASE WHEN a = 1 THEN 1 ELSE 0 END,
                            CASE WHEN a = 1 THEN 2 ELSE 0 END aliased,
                            CASE WHEN a = 1 THEN 3 ELSE 0 END as aliased2,
                            CASE WHEN a = 1 THEN 4 ELSE 0 END + 2 as END,
                            CASE WHEN a = 1 THEN 5 ELSE 0 END + 2 END,
                            CASE WHEN a = 1 THEN 6 ELSE 0 END + 2
                            FROM table'
                )
            )
            ->and($result->setResult($mockPgsqlResult))
            ->and($result->next())
            ->then
                ->array($result->current())
                    ->isEqualTo(
                        [
                            [
                                'name' => 'a',
                                'orgName' => 'a',
                                'table' => 'table',
                                'orgTable' => 'table',
                                'schema' => '',
                                'value' => 1
                            ],
                            [
                                'name' => 'CASE WHEN a = 1 THEN 1 ELSE 0 END',
                                'orgName' => 'CASE WHEN a = 1 THEN 1 ELSE 0 END',
                                'table' => '',
                                'orgTable' => '',
                                'schema' => '',
                                'value' => 1
                            ],
                            [
                                'name' => 'aliased',
                                'orgName' => 'CASE WHEN a = 1 THEN 2 ELSE 0 END',
                                'table' => '',
                                'orgTable' => '',
                                'schema' => '',
                                'value' => 2
                            ],
                            [
                                'name' => 'aliased2',
                                'orgName' => 'CASE WHEN a = 1 THEN 3 ELSE 0 END',
                                'table' => '',
                                'orgTable' => '',
                                'schema' => '',
                                'value' => 3
                            ],
                            [
                                'name' => 'END',
                                'orgName' => 'CASE WHEN a = 1 THEN 4 ELSE 0 END + 2',
                                'table' => '',
                                'orgTable' => '',
                                'schema' => '',
                                'value' => 6
                            ],
                            [
                                'name' => 'END',
                                'orgName' => 'CASE WHEN a = 1 THEN 5 ELSE 0 END + 2',
                                'table' => '',
                                'orgTable' => '',
                                'schema' => '',
                                'value' => 7
                            ],
                            [
                                'name' => 'CASE WHEN a = 1 THEN 6 ELSE 0 END + 2',
                                'orgName' => 'CASE WHEN a = 1 THEN 6 ELSE 0 END + 2',
                                'table' => '',
                                'orgTable' => '',
                                'schema' => '',
                                'value' => 8
                            ]
                        ]
                    )
        ;
    }
}
