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

namespace CCMBenchmark\Ting\Driver\Pgsql;

use CCMBenchmark\Ting\Driver\QueryException;
use CCMBenchmark\Ting\Driver\ResultInterface;

class Result implements ResultInterface
{

    protected $result          = null;
    protected $fields          = array();
    protected $iteratorOffset  = 0;
    protected $iteratorCurrent = null;

    public function __construct($result)
    {
        $this->result = $result;
    }

    public function setQuery($query)
    {
        $aliasToTable = array();
        $fields = array();

        preg_match_all(
            '/(?:join|from)\s+"?(?<table>[a-z0-9_]+)"?\s*(?:as)?\s*"?(?!on)(?<alias>[a-z0-9_]+)?"?(\s|$)/is',
            $query,
            $matches,
            PREG_SET_ORDER
        );
        foreach ($matches as $match) {
            $match['table'] = strtolower($match['table']);
            if ($match['alias'] !== '') {
                $aliasToTable[strtolower($match['alias'])] = $match['table'];
            } else {
                $aliasToTable[$match['table']] = $match['table'];
            }
        }

        $tableToAlias = array_flip($aliasToTable);
        preg_match_all('/select\s+(.+?)(\s+from|$)/is', $query, $matches, PREG_SET_ORDER);

        if (count($matches) === 0) {
            throw new queryException('Query invalid: can\'t parse columns');
        }

        $queryColumns = $matches[0][1];

        // We need a better solution
        if (preg_match('/(^|\s*)\*(\s*|$)/', $queryColumns) === 1) {
            throw new QueryException('Query invalid: usage of asterisk in column definition is forbidden');
        }

        preg_match_all(
            '/(?:(?:"?(?<table>[a-z0-9_]+)"?\.)?(?<column>[a-z0-9_]+)(?:\s*(?:as)?\s+(?<alias>[a-z0-9_]+))?)/is',
            $queryColumns,
            $matches,
            PREG_SET_ORDER
        );
        foreach ($matches as $match) {
            $stdClass = new \stdClass();
            $stdClass->orgname = $match['column'];

            if (isset($match['alias']) === true) {
                $stdClass->name = $match['alias'];
            } else {
                $stdClass->name = $stdClass->orgname;
            }

            if ($match['table'] !== '') {
                $stdClass->table    = strtolower($match['table']);
                $stdClass->orgtable = $aliasToTable[$stdClass->table];
            } else {
                $stdClass->orgtable = strtolower(pg_field_table($this->result, count($fields)));
                $stdClass->table    = $tableToAlias[$stdClass->orgtable];
            }

            $fields[] = $stdClass;
        }

        $this->fields = $fields;
    }

    public function dataSeek($offset)
    {
        return pg_result_seek($this->result, $offset);
    }

    public function format($data)
    {
        if ($data === false) {
            return null;
        }

        $columns = array();
        $data = array_values($data);

        foreach ($this->fields as $i => $rawField) {
            $column = array(
                'name'     => $rawField->name,
                'orgName'  => $rawField->orgname,
                'table'    => $rawField->table,
                'orgTable' => $rawField->orgtable,
                'value'    => $data[$i]
            );

            $columns[] = $column;
        }

        return $columns;
    }

    /**
     * Iterator
     */
    public function rewind()
    {
        $this->dataSeek(0);
        $this->iteratorOffset = -1;
        $this->next();
    }

    public function current()
    {
        return $this->iteratorCurrent;
    }

    public function key()
    {
        return $this->iteratorOffset;
    }

    public function next()
    {
        $this->iteratorCurrent = $this->format(pg_fetch_array($this->result, null, PGSQL_NUM));
        $this->iteratorOffset++;
    }

    public function valid()
    {
        if ($this->iteratorCurrent !== null) {
            return true;
        }

        return false;
    }
}
