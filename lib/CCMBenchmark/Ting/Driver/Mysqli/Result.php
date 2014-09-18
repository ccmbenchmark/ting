<?php

namespace CCMBenchmark\Ting\Driver\Mysqli;

class Result implements \CCMBenchmark\Ting\Driver\ResultInterface
{

    protected $result = null;
    protected $fields = array();
    protected $iteratorOffset = 0;
    protected $iteratorCurrent = null;

    public function __construct($result)
    {
        $this->result = $result;
        $this->fields = $this->result->fetch_fields();
    }

    public function dataSeek($offset)
    {
        return $this->result->data_seek($offset);
    }

    public function format($data)
    {
        if ($data === null) {
            return null;
        }

        $columns = array();
        $data = array_values($data);

        foreach ($this->fields as $i => $rawField) {
            $value = $data[$i];

            if (gettype($value) === 'string') {
                switch ($rawField->type){
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

            $column = array(
                'name'     => $rawField->name,
                'orgName'  => $rawField->orgname,
                'table'    => $rawField->table,
                'orgTable' => $rawField->orgtable,
                'value'    => $value
            );

            $columns[] = $column;
        }

        return $columns;
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
        $this->result->data_seek(0);
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
        $this->iteratorCurrent = $this->format($this->result->fetch_array(MYSQLI_NUM));
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
