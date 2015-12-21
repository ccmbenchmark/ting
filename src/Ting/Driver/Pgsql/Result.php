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

    const SQL_TABLE_SEPARATOR = 'inner|join|left|right|full|cross|where|group|having|window|union|intersect|except|order|limit|offset|fetch|for|on|using|natural';
    const PARSE_RAW_COLUMN = '/^\s*(?:"?(?P<table>[a-z_][a-z0-9_$]*)"?\.)?"?(?P<column>[a-z_][a-z0-9_$]*)"?(?:\s+as\s+"?(?P<alias>["a-z_]["a-z0-9_$]*))?"?\s*$/i';
    const PARSE_DYNAMIC_COLUMN = '/(?<prefix>\s+(as\s+))?"?(?P<alias>[a-z_][a-z0-9_$]*)?"?\s*$/';

    protected $connectionName  = null;
    protected $database        = null;
    protected $result          = null;
    protected $fields          = [];
    protected $iteratorOffset  = 0;
    protected $iteratorCurrent = null;

    /**
     * @param string $connectionName
     * @return $this
     */
    public function setConnectionName($connectionName)
    {
        $this->connectionName = (string) $connectionName;
        return $this;
    }

    /**
     * @param string $database
     * @return $this
     */
    public function setDatabase($database)
    {
        $this->database = (string) $database;
        return $this;
    }

    /**
     * @param object $result
     * @return $this
     */
    public function setResult($result)
    {
        $this->result = $result;
        return $this;
    }

    /**
     * @return string|null
     */
    public function getConnectionName()
    {
        return $this->connectionName;
    }

    /**
     * @return string|null
     */
    public function getDatabase()
    {
        return $this->database;
    }

    /**
     * Analyze the given query
     * @param $query
     * @throws QueryException
     *
     * @internal
     */
    public function setQuery($query)
    {
        $aliasToTable = [];
        $fields = [];

        preg_match_all(
            '/(?:join|from)\s+"?(?<table>[a-z_][a-z0-9_$]+)"?\s*(?:as)?\s*"?(?!\b('
            . self::SQL_TABLE_SEPARATOR . ')\b)(?<alias>[a-z_][a-z0-9_$]*)?"?(\s|$)/is',
            $query,
            $matches,
            PREG_SET_ORDER
        );

        foreach ($matches as $match) {
            $match['table'] = strtolower($match['table']);
            if ($match['alias'] !== '') {
                $tableToAlias[$match['table']] = strtolower($match['alias']);
            } else {
                $tableToAlias[$match['table']] = $match['table'];
            }
        }

        $tokens = preg_split('/(\W)/', strtolower($query), -1, PREG_SPLIT_NO_EMPTY|PREG_SPLIT_DELIM_CAPTURE);

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
            } elseif ($token === 'case' && $scope === 'column') {
                $scope = 'condition';
            } elseif ($token === 'end' && $scope === 'condition') {
                $scope = 'column';
            }

            if ($token === '(' && $scope !== 'string') {
                $brackets++;
            }

            if ($token === ')' && $scope !== 'string') {
                $brackets--;
            }

            if ($startCapture === true) {
                if ($brackets === 0) {
                    if ($token === ',' || $token === 'from') {
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
                                'column' => $matches['column']
                            ];

                            if (isset($matches['table']) === true) {
                                $columnComponent['table'] = $matches['table'];
                            }

                            if (isset($matches['alias']) === true) {
                                $columnComponent['alias'] = $matches['alias'];
                            }
                        } else { // Match dynamic column, ie : max(table.column), table.column || table.id, ...
                            $column = ltrim($column);
                            preg_match(self::PARSE_DYNAMIC_COLUMN, $column, $matches);

                            $cut = 0;

                            if (isset($matches['prefix']) === true) {
                                $cut += strlen($matches['prefix']);
                            }
                            if (isset($matches['alias']) === true) {
                                $cut += strlen($matches['alias']);
                            }

                            if ($cut > 0) {
                                $matches['column'] = substr($column, 0, - $cut);
                            } else {
                                $matches['column'] = $column;
                            }

                            $columnComponent = [
                                'complex' => true,
                                'table' => '',
                                'column' => $matches['column']
                            ];
                            if (isset($matches['alias']) === true) {
                                $columnComponent['alias'] = $matches['alias'];
                            }
                        }

                        $columnsMatches[] = $columnComponent;
                        $column = '';
                        if ($token === 'from') {
                            break;
                        }
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

            if ($token === 'select') {
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
            } elseif ($match['complex'] === false) {
                $stdClass->table = $tableToAlias[$stdClass->orgtable];
            } else {
                $stdClass->table = $stdClass->orgtable;
            }

            $fields[] = $stdClass;
        }

        $this->fields = $fields;

    }

    /**
     * Move the internal pointer to an arbitrary row in the result set
     * @param int $offset
     * @return bool
     */
    protected function dataSeek($offset)
    {
        return pg_result_seek($this->result, $offset);
    }

    /**
     * Format data
     * @param $data
     * @return array
     */
    protected function format($data)
    {
        if ($data === false) {
            return null;
        }

        $columns = [];
        $data = array_values($data);

        foreach ($this->fields as $i => $rawField) {
            $column = [
                'name'     => $this->unescapeField($rawField->name),
                'orgName'  => $this->unescapeField($rawField->orgname),
                'table'    => $this->unescapeField($rawField->table),
                'orgTable' => $this->unescapeField($rawField->orgtable),
                'value'    => $data[$i]
            ];

            $columns[] = $column;
        }

        return $columns;
    }

    /**
     * @return int
     */
    public function getNumRows()
    {
        return pg_num_rows($this->result);
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
        $this->iteratorCurrent = $this->format(pg_fetch_array($this->result, null, \PGSQL_NUM));
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
