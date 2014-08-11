<?php

namespace tests\fixtures\model;

class Bouh
{
    protected $id        = null;
    protected $firstname = null;
    protected $name      = null;

    public function setId($id)
    {
        $this->id = (int) $id;
    }

    public function setFirstname($firstname)
    {
        $this->firstname = (string) $firstname;
    }

    public function setName($name)
    {
        $this->name = (string) $name;
    }

    public function getId()
    {
        return (int) $this->id;
    }

    public function getFirstname()
    {
        return (string) $this->firstname;
    }

    public function getName()
    {
        return (string) $this->name;
    }
}
