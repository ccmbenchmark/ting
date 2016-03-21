<?php
/***********************************************************************
 *
 * Ting - PHP Datamapper
 * ==========================================
 *
 * Copyright (C) 2014 CCM Benchmark Group. (http://www.ccmbenchmark.com)
 *
 ***********************************************************************
 *
 * Licensed under the Apache License, Version 2.0 (the "License"); you
 * may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 *
 *     http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or
 * implied. See the License for the specific language governing
 * permissions and limitations under the License.
 *
 **********************************************************************/

namespace tests\fixtures\model;

use CCMBenchmark\Ting\Entity\NotifyProperty;
use CCMBenchmark\Ting\Entity\NotifyPropertyInterface;

class BouhMySchema implements NotifyPropertyInterface
{

    use NotifyProperty;

    protected $id        = null;
    protected $firstname = null;
    protected $name      = null;
    protected $enabled   = null;
    protected $price     = null;
    protected $roles     = ['USER'];
    protected $city      = null;
    protected $retrievedTime = null;

    public function setId($id)
    {
        $this->propertyChanged('id', $this->id, $id);
        $this->id = $id;
    }

    public function setFirstname($firstname)
    {
        $this->propertyChanged('firstname', $this->firstname, $firstname);
        $this->firstname = 'MySchema' . $firstname;
    }

    public function setName($name)
    {
        $this->propertyChanged('name', $this->name, $name);
        $this->name = 'MySchema' . $name;
    }

    public function setRoles(array $roles)
    {
        $this->propertyChanged('roles', $this->roles, $roles);
        $this->roles = $roles;
    }

    public function setEnabled($enabled)
    {
        $this->propertyChanged('enabled', $this->enabled, $enabled);
        $this->enabled = $enabled;
    }

    public function setPrice($price)
    {
        $this->propertyChanged('price', $this->price, $price);
        $this->price = $price;
    }

    public function getId()
    {
        return $this->id;
    }

    public function getFirstname()
    {
        return $this->firstname;
    }

    public function getName()
    {
        return $this->name;
    }

    public function getRoles()
    {
        return $this->roles;
    }

    public function getEnabled()
    {
        return $this->enabled;
    }

    public function getPrice()
    {
        return $this->price;
    }

    public function setRetrievedTime($time)
    {
        $this->retrievedTime = $time;
    }

    public function getRetrievedTime()
    {
        return $this->retrievedTime;
    }

    public function setCity(City $city)
    {
        $this->city = $city;
    }

    public function getCity()
    {
        return $this->city;
    }
}
