<?php

namespace fastorm\Driver\Mysqli;

class Result implements \fastorm\Driver\ResultInterface
{

    protected $result = null;
    protected $fields = array();

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
     */
    public function rewind()
    {
        $this->result->rewind();
    }

    public function current()
    {
        return $this->format($this->result->current());
    }

    public function key()
    {
        $this->result->key();
    }

    public function next()
    {
        $this->result->next();
    }

    public function valid()
    {
        return $this->result->valid();
    }
}
