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
        $this->fields = $this->result->fetch_fields();
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
     * Move the internal result pointer to an arbitrary row
     * @param $offset
     * @return mixed
     */
    protected function dataSeek($offset)
    {
        if ($this->result !== null) {
            return $this->result->data_seek($offset);
        }
    }

    /**
     * Format output
     * @param $data
     * @return array|null
     */
    protected function format($data)
    {
        if ($data === null) {
            return null;
        }

        $columns = [];
        $data = array_values($data);

        foreach ($this->fields as $i => $rawField) {
            $value = $data[$i];

            if (gettype($value) === 'string') {
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

    /**
     * @return int
     */
    public function getNumRows()
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
    public function rewind()
    {
        if ($this->result !== null) {
            $this->result->data_seek(0);
            $this->iteratorOffset = -1;
            $this->next();
        }
    }

    /**
     * Return current row
     * @return mixed|null
     */
    public function current()
    {
        return $this->iteratorCurrent;
    }

    /**
     * Return the key of the actual row
     * @return int|mixed
     */
    public function key()
    {
        return $this->iteratorOffset;
    }

    /**
     * Move to the next row in result set
     */
    public function next()
    {
        if ($this->result !== null) {
            $this->iteratorCurrent = $this->format($this->result->fetch_array(MYSQLI_NUM));

            $this->iteratorOffset++;
        }
    }

    /**
     * Is the actual row valid
     * @return bool
     */
    public function valid()
    {
        return $this->iteratorCurrent !== null;
    }
}
