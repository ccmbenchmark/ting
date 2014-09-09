<?php

namespace tests\fixtures\FakeDriver;

class MysqliResult implements \Iterator
{

    protected $offset = 0;
    protected $data   = null;

    public function __construct($data)
    {
        $this->data = $data;
    }

    // @codingStandardsIgnoreStart
    public function fetch_fields()
    {

    }

    public function data_seek()
    {

    }

    public function fetch_array($type)
    {
        $data = $this->current();
        $this->next();
        return $data;
    }
    // @codingStandardsIgnoreEnd

    public function rewind()
    {
        $this->offset = 0;
    }

    public function current()
    {
        return $this->data[$this->offset];
    }

    public function key()
    {
        return $this->offset;
    }

    public function next()
    {
        $this->offset++;
    }

    public function valid()
    {
        return isset($this->data[$this->offset]);
    }
}
