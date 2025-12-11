<?php

namespace tests\fixtures\ValueObject;

class Bouh
{
    private $firstname;

    private $name;

    public function __construct($firstname, $name)
    {
        $this->firstname = $firstname;
        $this->name = $name;
    }

    /**
     * @return mixed
     */
    public function getFirstname()
    {
        return $this->firstname;
    }

    /**
     * @return mixed
     */
    public function getName()
    {
        return $this->name;
    }
}
