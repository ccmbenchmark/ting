<?php

namespace tests\fixtures\model;

class Bouh
{

    protected $firstname = null;
    protected $name = null;

    public function setFirstname($firstname)
    {
        $this->firstname = $firstname;
    }

    public function setName($name)
    {
        $this->name = $name;
    }

    public function getFirstname()
    {
        return $this->firstname;
    }

    public function getName()
    {
        return $this->name;
    }
}
