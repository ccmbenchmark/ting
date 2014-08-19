<?php

namespace fastorm\Entity;

use fastorm\Driver\ResultInterface;

class Collection implements \Iterator
{

    protected $result   = null;
    protected $hydrator = null;

    public function set(ResultInterface $result)
    {
        $this->result = $result;
    }

    public function hydrate($data)
    {
        if ($data === null) {
            return null;
        }

        if ($this->hydrator !== null) {
            return $this->hydrator->hydrate($data);
        }

        return $data;
    }

    public function hydrator(Hydrator $hydrator)
    {
        $this->hydrator = $hydrator;
        return $this;
    }

    /**
     * Iterator
     */
    public function rewind()
    {
        $this->result->rewind();
        return $this;
    }

    public function current()
    {
        return $this->hydrate($this->result->current());
    }

    public function key()
    {
        return $this->result->key();
    }

    public function next()
    {
        $this->result->next();
        return $this;
    }

    public function valid()
    {
        return $this->result->valid();
    }
}
