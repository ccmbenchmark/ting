<?php

namespace tests\units\CCMBenchmark\Ting\Driver\Pgsql;

use \mageekguy\atoum;

class Result extends atoum
{
    public function testSetQueryShouldRaiseExceptionOnColumnAsterisk()
    {
        $this
            ->if($result = new \CCMBenchmark\Ting\Driver\Pgsql\Result('result resource'))
            ->exception(function () use ($result) {
                $result->setQuery('select t.* from table as t');
            })
                ->hasMessage('Query invalid: usage of asterisk in column definition is forbidden');
    }

    public function testSetQueryShouldRaiseExceptionParseColumns()
    {
        $this
            ->if($result = new \CCMBenchmark\Ting\Driver\Pgsql\Result('result resource'))
            ->exception(function () use ($result) {
                $result->setQuery('selectcolumn from table');
            })
                ->hasMessage('Query invalid: can\'t parse columns');
    }

    public function testDataSeekShouldCallPgResultSeek()
    {
        $this->function->pg_result_seek = function ($result, $index) use (&$outerIndex) {
            $outerIndex = $index;
            return true;
        };

        $this
            ->if($result = new \CCMBenchmark\Ting\Driver\Pgsql\Result('result resource'))
            ->then($result->dataSeek(789))
            ->integer($outerIndex)
                ->isIdenticalTo(789);
    }

    public function testFormat()
    {
        $this->function->pg_field_table = function ($result, $index) {
            if ($index === 0) {
                return 'T_BOUH_BOO';
            }
            return false;
        };

        $this
            ->if($result = new \CCMBenchmark\Ting\Driver\Pgsql\Result('result resource'))
            ->then($result->setQuery('SELECT firstname as prenom, bouh.name as nom FROM T_BOUH_BOO as bouh'))
            ->then($row = $result->format(array('firstname' => 'Sylvain', 'name' => 'Robez-Masson')))
            ->string($row[0]['name'])
                ->isIdenticalTo('prenom')
            ->string($row[0]['orgName'])
                ->isIdenticalTo('firstname')
            ->string($row[0]['table'])
                ->isIdenticalTo('bouh')
            ->string($row[0]['orgTable'])
                ->isIdenticalTo('t_bouh_boo')
            ->string($row[0]['value'])
                ->isIdenticalTo('Sylvain')
            ->string($row[1]['name'])
                ->isIdenticalTo('nom')
            ->string($row[1]['orgName'])
                ->isIdenticalTo('name')
            ->string($row[1]['table'])
                ->isIdenticalTo('bouh')
            ->string($row[1]['orgTable'])
                ->isIdenticalTo('t_bouh_boo')
            ->string($row[1]['value'])
                ->isIdenticalTo('Robez-Masson');
    }

    public function testFormatWithoutAlias()
    {
        $this->function->pg_field_table = function ($result, $index) {
            if ($index === 0) {
                return 't_bouh_boo';
            }
            return false;
        };

        $this
            ->if($result = new \CCMBenchmark\Ting\Driver\Pgsql\Result('result resource'))
            ->then($result->setQuery('SELECT firstname, T_BOUH_BOO.name FROM T_BOUH_BOO'))
            ->then($row = $result->format(array('firstname' => 'Sylvain', 'name' => 'Robez-Masson')))
            ->string($row[0]['name'])
                ->isIdenticalTo('firstname')
            ->string($row[0]['orgName'])
                ->isIdenticalTo('firstname')
            ->string($row[0]['table'])
                ->isIdenticalTo('t_bouh_boo')
            ->string($row[0]['orgTable'])
                ->isIdenticalTo('t_bouh_boo')
            ->string($row[0]['value'])
                ->isIdenticalTo('Sylvain')
            ->string($row[1]['name'])
                ->isIdenticalTo('name')
            ->string($row[1]['orgName'])
                ->isIdenticalTo('name')
            ->string($row[1]['table'])
                ->isIdenticalTo('t_bouh_boo')
            ->string($row[1]['orgTable'])
                ->isIdenticalTo('t_bouh_boo')
            ->string($row[1]['value'])
                ->isIdenticalTo('Robez-Masson');
    }

    public function testFormatShouldReturnNull()
    {
        $this
            ->if($result = new \CCMBenchmark\Ting\Driver\Pgsql\Result('result resource'))
            ->variable($result->format(false))
                ->isNull();
    }

    public function testIterator()
    {
        $this->function->pg_result_seek = true;
        $this->function->pg_fetch_array = array();

        $this
            ->if($result = new \mock\CCMBenchmark\Ting\Driver\Pgsql\Result('result resource'))
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
        $this->function->pg_result_seek = true;
        $this->function->pg_fetch_array = false;

        $this
            ->if($result = new \CCMBenchmark\Ting\Driver\Pgsql\Result('result resource'))
            ->then($result->rewind())
            ->then($result->next())
            ->boolean($result->valid())
                ->isFalse();
    }
}
