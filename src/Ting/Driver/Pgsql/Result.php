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

use stdClass;
use CCMBenchmark\Ting\Driver\QueryException;
use CCMBenchmark\Ting\Driver\ResultInterface;

class Result implements ResultInterface
{
    public const SQL_TABLE_SEPARATOR = 'inner|join|left|right|full|cross|where|group|having|window|union|intersect|except|order|limit|offset|fetch|for|on|using|natural';
    public const PARSE_RAW_COLUMN = '/^\s*(?:"?(?P<table>[a-z_][a-z0-9_$]*)"?\.)?"?(?P<column>[a-z_][a-z0-9_$]*)"?(?:\s+as\s+"?(?P<alias>["a-z_]["a-z0-9_$]*))?"?\s*$/i';
    public const PARSE_DYNAMIC_COLUMN = '/(?<prefix>\s+(as\s+))?"?(?P<alias>[a-z_][a-z0-9_$]*)?"?\s*$/i';

    protected ?string $connectionName = null;
    protected ?string $database = null;
    /** @var \PgSql\Result|null */
    protected $result = null;
    protected array $fields = [];
    protected int $iteratorOffset = 0;
    protected ?array $iteratorCurrent = null;

    /**
     * @param string $connectionName
     * @return $this
     */
    public function setConnectionName(string $connectionName): static
    {
        $this->connectionName = (string) $connectionName;
        return $this;
    }

    /**
     * @param string $database
     * @return $this
     */
    public function setDatabase($database): static
    {
        $this->database = (string) $database;
        return $this;
    }

    /**
     * @param \PgSql\Result $result
     * @return $this
     */
    public function setResult($result): static
    {
        $this->result = $result;
        return $this;
    }

    public function getConnectionName(): ?string
    {
        return $this->connectionName;
    }

    public function getDatabase(): ?string
    {
        return $this->database;
    }

    /**
     * Analyze the given query
     * @throws QueryException
     *
     * @internal
     */
    public function setQuery(string $query): void
    {
        $tableToAlias = [];
        $aliasToSchema = [];
        $fields = [];

        preg_match_all(
            '/(?:join|from)\s+(?:"?(?<schema>[a-z_][a-z0-9_$]+)"?.)*?"?(?<table>[a-z_][a-z0-9_$]+)"?\s*(?:as)?\s*"?(?!\b('
            . self::SQL_TABLE_SEPARATOR . ')\b)(?<alias>[a-z_][a-z0-9_$]*)?"?(\s|$)/is',
            (string) $query,
            $matches,
            PREG_SET_ORDER
        );

        foreach ($matches as $match) {
            $match['table'] = strtolower($match['table']);
            if ($match['alias'] !== '') {
                $tableToAlias[$match['table']] = strtolower($match['alias']);
                $aliasToSchema[strtolower($match['alias'])] = strtolower($match['schema']);
            } else {
                $tableToAlias[$match['table']] = $match['table'];
                $aliasToSchema[$match['table']] = strtolower($match['schema']);
            }
        }

        $tokens = preg_split('/(\W)/', strtolower((string) $query), -1, PREG_SPLIT_NO_EMPTY | PREG_SPLIT_DELIM_CAPTURE);
        $tokensWithCase = preg_split('/(\W)/', (string) $query, -1, PREG_SPLIT_NO_EMPTY | PREG_SPLIT_DELIM_CAPTURE);

        if ($tokens === false) {
            return;
        }

        $startCapture = false;
        $columnsMatches = [];
        $column = '';
        $scope  = 'column';
        $brackets = 0;
        $totalTokens = count($tokens);
        $noAlias = false;

        foreach ($tokens as $index => $token) {
            if ($token === '\'') {
                $scope = $scope === 'string' ? 'column' : 'string';
            } elseif ($token === 'case' && $scope === 'column') {
                $scope = 'condition';
                $noAlias = true;
            } elseif ($token === 'end' && $scope === 'condition') {
                $scope = 'column';
            }

            if ($token === '(' && $scope !== 'string') {
                $brackets++;
            }

            if ($token === ')' && $scope !== 'string') {
                $brackets--;
            }

            if ($startCapture) {
                if ($brackets === 0 && ($token === ',' || $token === 'from' || $index === $totalTokens - 1)) {
                    $scope = 'column';

                    if ($index === $totalTokens - 1 && $token !== ';') {
                        $column .= $token;
                    }

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

                        if (isset($matches['table'])) {
                            $columnComponent['table'] = $matches['table'];
                        }

                        if (isset($matches['alias'])) {
                            $columnComponent['alias'] = $matches['alias'];
                        }
                    } else { // Match dynamic column, ie : max(table.column), table.column || table.id, ...
                        $column = trim($column);
                        preg_match(self::PARSE_DYNAMIC_COLUMN, $column, $matches);

                        $cut = 0;

                        if ($noAlias === false) {
                            if (isset($matches['prefix'])) {
                                $cut += \strlen($matches['prefix']);
                            }
                            if (isset($matches['alias'])) {
                                $cut += \strlen($matches['alias']);
                            }
                        }

                        $matches['column'] = $cut > 0 ? trim(substr($column, 0, - $cut)) : $column;

                        $columnComponent = [
                            'complex' => true,
                            'table' => '',
                            'column' => $matches['column']
                        ];
                        if ($noAlias === false && isset($matches['alias'])) {
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

                if (in_array($scope, ['column', 'string', 'condition'], true) && isset($tokensWithCase[$index])) {
                    $column .= $tokensWithCase[$index];
                }

                if ($scope === 'column' && $token === '*') {
                    throw new QueryException('Query invalid: usage of asterisk in column definition is forbidden');
                }

                if ($scope === 'column' && $token !== 'end') {
                    $noAlias = false;
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
            $stdClass = new stdClass();
            $stdClass->orgname = $match['column'];

            $stdClass->name = isset($match['alias']) === true ? $match['alias'] : $stdClass->orgname;

            $table = pg_field_table($this->result, count($fields));
            if ($table === false) {
                $table = '';
            }
            $stdClass->orgtable = $match['complex'] === false ? strtolower((string) $table) : '';

            if ($match['table'] !== '') {
                $stdClass->table = strtolower($match['table']);
            } elseif ($match['complex'] === false) {
                $stdClass->table = $tableToAlias[$stdClass->orgtable];
            } else {
                $stdClass->table = $stdClass->orgtable;
            }

            $stdClass->schema = isset($aliasToSchema[$stdClass->table]) === true ? $aliasToSchema[$stdClass->table] : '';

            $stdClass->name     = $this->unescapeField($stdClass->name);
            $stdClass->orgname  = $this->unescapeField($stdClass->orgname);
            $stdClass->table    = $this->unescapeField($stdClass->table);
            $stdClass->orgtable = $this->unescapeField($stdClass->orgtable);
            $stdClass->schema   = $this->unescapeField($stdClass->schema);

            $fields[] = $stdClass;
        }

        $this->fields = $fields;
    }

    /**
     * Move the internal pointer to an arbitrary row in the result set
     */
    protected function dataSeek(int $offset): bool
    {
        return pg_result_seek($this->result, $offset);
    }

    /**
     * Format data
     */
    protected function format(array|false $data): ?array
    {
        if ($data === false) {
            return null;
        }

        $columns = [];
        foreach ($this->fields as $i => $rawField) {
            $columns[] = [
                'name'     => $rawField->name,
                'orgName'  => $rawField->orgname,
                'table'    => $rawField->table,
                'orgTable' => $rawField->orgtable,
                'schema'   => $rawField->schema,
                'value'    => $data[$i]
            ];
        }

        return $columns;
    }

    public function getNumRows(): int
    {
        return pg_num_rows($this->result);
    }

    /**
     * Unescape the given field name according to PGSQL Standards
     */
    protected function unescapeField(string $field): string
    {
        return trim( $field, '"');
    }

    /**
     * Iterator
     */
    public function rewind(): void
    {
        $this->dataSeek(0);
        $this->iteratorOffset = -1;
        $this->next();
    }

    public function current(): mixed
    {
        return $this->iteratorCurrent;
    }

    public function key(): mixed
    {
        return $this->iteratorOffset;
    }

    public function next(): void
    {
        $this->iteratorCurrent = $this->format(pg_fetch_array($this->result, null, \PGSQL_NUM));
        $this->iteratorOffset++;
    }

    public function valid(): bool
    {
        return $this->iteratorCurrent !== null;
    }
}
