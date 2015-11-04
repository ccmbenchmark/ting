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

    /**
     * @param resource $result
     */
    public function __construct($result)
    {
        $this->result = $result;
    }

    /**
     * Analyze the given query
     * @param $query
     * @throws QueryException
     */
    public function setQuery($query)
    {
        $aliasToTable = [];
        $fields = [];

        $num = pg_num_fields($this->result);

        $columns = [];

        for ($i = 0; $i < $num; $i++) {
            $columns[] = ['table' => pg_field_table($this->result, $i), 'column' => pg_field_name($this->result, $i)];
        }

        $tokens = preg_split('/(\W)/', $query, -1, PREG_SPLIT_NO_EMPTY|PREG_SPLIT_DELIM_CAPTURE);

        $rawQueryColumns = '';
        $brackets = 0;
        $startCapture = false;

        foreach ($tokens as $token) {
            if ($token === '(') {
                $brackets++;
            }

            if ($token === ')') {
                $brackets--;
            }

            if (strtolower($token) === 'from' && $brackets === 0) {
                break;
            }

            if ($startCapture === true) {
                $rawQueryColumns .= $token;
            }

            if (strtolower($token) === 'select') {
                $startCapture = true;
            }
        }

        if ($rawQueryColumns === '') {
            throw new QueryException('Query invalid: can\'t parse columns');
        }

        // We need a better solution
        if (preg_match('/(^|\s*)\*(\s*|$)/', $rawQueryColumns) === 1) {
            throw new QueryException('Query invalid: usage of asterisk in column definition is forbidden');
        }

        preg_match_all(
            '/(?J)((?P<column>\(.+\))|(?:(?P<table>[^\.\s]+)\.)?(?P<column>[^\s,]+))(\s+as)?\s*(?P<alias>[^\s,.]+)?/is',
            $rawQueryColumns,
            $columnsMatches,
            PREG_SET_ORDER
        );

        foreach ($columnsMatches as $index => $column) {
            $tableAndColumn = [];

            if ($column['table'] !== '') {
                $tableAndColumn[] = $column['table'];
            }

            $tableAndColumn[] = $column['column'];

            if (count($tableAndColumn) === 2) {
                $regex = $columns[$index]['table'] . '(?:\sas)?\s(?P<alias>' . $tableAndColumn[0] . ')';
                preg_match('/' . $regex . '/i', $query, $matches);

                if (isset($matches['alias']) === true) {
                    $aliasToTable[strtolower($matches['alias'])] = strtolower($columns[$index]['table']);
                } else {
                    $aliasToTable[strtolower($tableAndColumn[0])] = strtolower($tableAndColumn[0]);
                }
            }
        }

        $tableToAlias = array_flip($aliasToTable);

        foreach ($columnsMatches as $match) {
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

                if (isset($tableToAlias[$stdClass->orgtable]) === false) {
                    $stdClass->table = $stdClass->orgtable;
                } else {
                    $stdClass->table = $tableToAlias[$stdClass->orgtable];
                }
            }

            $fields[] = $stdClass;
        }

        $this->fields = $fields;
    }

    /**
     * Move the internal pointer to an arbitrary row in the result set
     * @param $offset
     * @return bool
     */
    public function dataSeek($offset)
    {
        return pg_result_seek($this->result, $offset);
    }

    /**
     * Format data
     * @param $data
     * @return array
     */
    public function format($data)
    {
        if ($data === false) {
            return null;
        }

        $columns = array();
        $data = array_values($data);

        foreach ($this->fields as $i => $rawField) {
            $column = array(
                'name'     => $this->unescapeField($rawField->name),
                'orgName'  => $this->unescapeField($rawField->orgname),
                'table'    => $this->unescapeField($rawField->table),
                'orgTable' => $this->unescapeField($rawField->orgtable),
                'value'    => $data[$i]
            );

            $columns[] = $column;
        }

        return $columns;
    }

    /**
     * Unescape the given field name according to PGSQL Standards
     * @param $field
     * @return string
     */
    protected function unescapeField($field)
    {
        return trim($field, '"');
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
