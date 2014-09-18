<?php

namespace sample\src\model;

use CCMBenchmark\Ting\NotifyPropertyInterface;
use CCMBenchmark\Ting\PropertyListenerInterface;

class City implements NotifyPropertyInterface
{
    protected $listeners   = array();
    protected $id          = null;
    protected $name        = null;
    protected $countryCode = null;
    protected $district    = null;
    protected $population  = null;

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

    public function getId()
    {
        return (int) $this->id;
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

    public function setCountryCode($countryCode)
    {
        $this->propertyChanged('countryCode', $this->countryCode, $countryCode);
        $this->countryCode = (string) $countryCode;
    }

    public function getCountryCode()
    {
        return (string) $this->countryCode;
    }

    public function setDistrict($district)
    {
        $this->propertyChanged('district', $this->district, $district);
        $this->district = (string) $district;
    }

    public function getDistrict()
    {
        return (string) $this->district;
    }

    public function setPopulation($population)
    {
        $this->propertyChanged('population', $this->population, $population);
        $this->population = (int) $population;
    }

    public function getPopulation()
    {
        return (int) $this->population;
    }
}
