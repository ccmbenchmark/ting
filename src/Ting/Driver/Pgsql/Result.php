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

    const PARSE_RAW_COLUMN =
        '/^\s*(?:(?P<table>[0-9a-z_]+)\.)?(?P<column>[0-9a-z_]+)(?:\s+as\s+(?P<alias>[0-9a-z_]+))?\s*$/i';

    const PARSE_DYNAMIC_COLUMN =
        '/^\s*(?P<column>.+?(?=as|$))\s*(?:as)?\s*(?P<alias>.+)?$/i';

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
        $fields = [];

        $num = pg_num_fields($this->result);

        $columns = [];

        for ($i = 0; $i < $num; $i++) {
            $columns[] = ['table' => pg_field_table($this->result, $i), 'column' => pg_field_name($this->result, $i)];
        }

        $tokens = preg_split('/(\W)/', $query, -1, PREG_SPLIT_NO_EMPTY|PREG_SPLIT_DELIM_CAPTURE);

        $startCapture = false;
        $columnsMatches = [];
        $column = '';
        $scope  = 'column';
        $brackets = 0;

        foreach ($tokens as $token) {
            if ($token === '\'') {
                if ($scope === 'string') {
                    $scope = 'column';
                } else {
                    $scope = 'string';
                }
            }

            if ($token === '(' && $scope !== 'string') {
                $brackets++;
            }

            if ($token === ')' && $scope !== 'string') {
                $brackets--;
            }

            if ($startCapture === true) {
                if ($brackets === 0) {
                    if ($token === ',' || strtolower($token) === 'from') {
                        $scope = 'column';

                        /**
                         * Match column format table.column (as alias)
                         */
                        preg_match(
                            self::PARSE_RAW_COLUMN,
                            $column,
                            $matches
                        );

                        if ($matches !== []) {
                            $columnComponent = [
                                'complex' => false,
                                'column' => trim($matches['column'])
                            ];

                            if (isset($matches['table']) === true) {
                                $columnComponent['table'] = trim($matches['table']);
                            }

                            if (isset($matches['alias']) === true) {
                                $columnComponent['alias'] = trim($matches['alias']);
                            }
                        } else { // Match complex column, ie : max(table.column), table.column || table.id, ...
                            preg_match(self::PARSE_DYNAMIC_COLUMN, $column, $matches);
                            $columnComponent = [
                                'complex' => true,
                                'table' => '',
                                'column' => trim($matches['column'])
                            ];
                            if (isset($matches['alias']) === true) {
                                $columnComponent['alias'] = trim($matches['alias']);
                            }
                        }

                        $columnsMatches[] = $columnComponent;
                        $column = '';
                        continue;
                    }
                }

                if ($scope === 'column' || $scope === 'string') {
                    $column .= $token;
                }

                if ($scope === 'column' && $token === '*') {
                    throw new QueryException('Query invalid: usage of asterisk in column definition is forbidden');
                }
            }

            if (strtolower($token) === 'from' && $brackets === 0) {
                break;
            }

            if (strtolower($token) === 'select') {
                $startCapture = true;
            }
        }

        if ($columnsMatches === []) {
            throw new QueryException('Query invalid: can\'t parse columns');
        }

        foreach ($columnsMatches as $match) {
            $stdClass = new \stdClass();
            $stdClass->orgname = $match['column'];

            if (isset($match['alias']) === true) {
                $stdClass->name = $match['alias'];
            } else {
                $stdClass->name = $stdClass->orgname;
            }

            if ($match['complex'] === false) {
                $stdClass->orgtable = strtolower(pg_field_table($this->result, count($fields)));
            } else {
                $stdClass->orgtable = '';
            }

            if ($match['table'] !== '') {
                $stdClass->table = strtolower($match['table']);
            } else {
                $stdClass->table = $stdClass->orgtable;
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
