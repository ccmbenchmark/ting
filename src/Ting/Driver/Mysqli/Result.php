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

namespace CCMBenchmark\Ting\Driver\Mysqli;

use CCMBenchmark\Ting\Driver\ResultInterface;

class Result implements ResultInterface
{
    protected ?string $connectionName = null;
    protected ?string $database = null;
    /** @var \mysqli_result|null */
    protected $result = null;
    /** @var array<int, object{name: string, orgname: string, table: string, orgtable: string, def: string, db: string, catalog: string, max_length: int, length: int, charsetnr: string, flags: int, type: int, decimals: int}> $fields  */
    protected array $fields = [];
    protected int $iteratorOffset = 0;
    /** @var array|false|object|null */
    protected $iteratorCurrent = null;
    /** @var class-string|null  */
    protected ?string $objectToFetch = null;

    public function setConnectionName(string $connectionName): static
    {
        $this->connectionName = $connectionName;
        return $this;
    }

    public function setDatabase(string $database): static
    {
        $this->database = $database;
        return $this;
    }

    /**
     * @param \mysqli_result $result
     * Typehinting $result would need rewriting all related unit tests
     */
    public function setResult($result): static
    {
        $this->result = $result;
        $this->fields = $this->result->fetch_fields();
        return $this;
    }

    /**
     * @param class-string $objectToFetch
     */
    public function setObjectToFetch(string $objectToFetch): static
    {
        $this->objectToFetch = $objectToFetch;
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
     * Move the internal result pointer to an arbitrary row
     */
    protected function dataSeek(int $offset): bool|null
    {
        if ($this->result !== null) {
            return $this->result->data_seek($offset);
        }
        return null;
    }

    /**
     * Format output
     * @param $data
     * @return array|null
     */
    protected function format(array|null|false $data): ?array
    {
        if ($data === null || $data === false) {
            return null;
        }

        $columns = [];
        $data = array_values($data);

        foreach ($this->fields as $i => $rawField) {
            $value = $data[$i];

            if (\is_string($value)) {
                switch ($rawField->type) {
                    case MYSQLI_TYPE_DECIMAL:
                        // Decimal
                        // Next lines are all mapped to float
                    case MYSQLI_TYPE_DOUBLE:
                        // Double
                    case MYSQLI_TYPE_NEWDECIMAL:
                        // Mysql 5.0.3 + : Decimal or numeric
                    case MYSQLI_TYPE_FLOAT:
                        // Float
                        $value = (float)$value;
                        break;
                    case MYSQLI_TYPE_TINY:
                        // Tinyint
                    case MYSQLI_TYPE_INT24:
                        // MediumInt
                    case MYSQLI_TYPE_LONG:
                        // Int
                    case MYSQLI_TYPE_SHORT:
                        //SMALLINT
                        $value = (int)$value;
                        break;
                    case MYSQLI_TYPE_NULL:
                        // Type null
                        $value = null;
                        break;
                    case MYSQLI_TYPE_LONGLONG:
                        // Bigint, bigger than PHP_INT_MAX
                        // These case is here as a reminder
                        $value = (string)$value;
                        break;
                }
            }

            $column = [
                'name'     => $rawField->name,
                'orgName'  => $rawField->orgname,
                'table'    => $rawField->table,
                'orgTable' => $rawField->orgtable,
                'value'    => $value
            ];

            $columns[] = $column;
        }

        return $columns;
    }


    public function getNumRows(): int|string
    {
        return $this->result->num_rows;
    }

    /**
     * Iterator
     *
     * @internal Don't use mysqli_result iterator system
     *  When you do a jointure like that :
     *  select * from city c inner join country co on (c.cou_code = co.cou_code)
     *  the second column "cou_code" is missing, cause current() use an associative array
     */
    public function rewind(): void
    {
        if ($this->result !== null) {
            $this->result->data_seek(0);
            $this->iteratorOffset = -1;
            $this->next();
        }
    }

    /**
     * Return current row
     */
    public function current(): mixed
    {
        return $this->iteratorCurrent;
    }

    /**
     * Return the key of the actual row
     */
    public function key(): mixed
    {
        return $this->iteratorOffset;
    }

    /**
     * Move to the next row in result set
     */
    public function next(): void
    {
        if ($this->result !== null) {
            if ($this->objectToFetch !== null) {
                $this->iteratorCurrent = $this->result->fetch_object($this->objectToFetch);
            } else {
                $this->iteratorCurrent = $this->format($this->result->fetch_array(MYSQLI_NUM));
            }

            $this->iteratorOffset++;
        }
    }

    /**
     * Is the actual row valid
     * @return bool
     */
    public function valid(): bool
    {
        return $this->iteratorCurrent !== null;
    }
}
