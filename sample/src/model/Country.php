<?php

namespace sample\src\model;

use fastorm\NotifyPropertyInterface;
use fastorm\PropertyListenerInterface;

class Country implements NotifyPropertyInterface
{

    protected $code      = null;
    protected $name      = null;
    protected $continent = null;
    protected $region    = null;
    protected $president = null;
    protected $listeners = [];

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

    public function setCode($code)
    {
        $this->propertyChanged('code', $this->code, $code);
        $this->code = (string) $code;
    }

    public function getCode()
    {
        return (string) $this->code;
    }

    public function setName($name)
    {
        $this->propertyChanged('name', $this->name, $name);
        $this->name = (string) $name;
    }

    public function getName()
    {
        return (string) $this->name;
    }

    public function setContinent($continent)
    {
        $this->propertyChanged('continent', $this->continent, $continent);
        $this->continent = (string) $continent;
    }

    public function getContinent()
    {
        return (string) $this->continent;
    }

    public function setRegion($region)
    {
        $this->propertyChanged('region', $this->region, $region);
        $this->region = (string) $region;
    }

    public function getRegion()
    {
        return (string) $this->region;
    }

    public function setPresident($president)
    {
        $this->propertyChanged('president', $this->president, $president);
        $this->president = (string) $president;
    }

    public function getPresident()
    {
        return (string) $this->president;
    }
}
