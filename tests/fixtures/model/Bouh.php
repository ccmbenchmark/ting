<?php

namespace tests\fixtures\model;

use CCMBenchmark\Ting\NotifyPropertyInterface;
use CCMBenchmark\Ting\PropertyListenerInterface;

class Bouh implements NotifyPropertyInterface
{
    protected $listeners   = array();
    protected $id        = null;
    protected $firstname = null;
    protected $name      = null;

    public function addPropertyListener(PropertyListenerInterface $listener)
    {
        $this->listeners[] = $listener;
    }

    public function propertyChanged($propertyName, $oldValue, $newValue)
    {
        if ($oldValue === $newValue) {
            return;
        }

        foreach ($this->listeners as $listener) {
            $listener->propertyChanged($this, $propertyName, $oldValue, $newValue);
        }
    }

    public function setId($id)
    {
        $this->propertyChanged('id', $this->id, $id);
        $this->id = (int) $id;
    }

    public function setFirstname($firstname)
    {
        $this->propertyChanged('firstname', $this->firstname, $firstname);
        $this->firstname = (string) $firstname;
    }

    public function setName($name)
    {
        $this->propertyChanged('name', $this->name, $name);
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
