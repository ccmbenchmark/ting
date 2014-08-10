<?php

namespace fastorm\Driver\Mysqli;

class Result implements \fastorm\Driver\ResultInterface
{

    protected $result = null;
    protected $fields = array();
    protected $iteratorOffset = 0;
    protected $iteratorCurrent = null;

    public function __construct($result)
    {
        $this->result = new \IteratorIterator($result);
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
