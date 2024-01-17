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

class City implements NotifyPropertyInterface
{

    use NotifyProperty;

    protected $id        = null;
    protected $name      = null;
    protected $zipcode   = null;
    protected $bouhId    = null;
    protected $parks     = [];
    protected $department = null;

    public function setId($id)
    {
        $this->propertyChanged('id', $this->id, $id);
        $this->id = $id;
    }

    public function setName($name)
    {
        $this->propertyChanged('name', $this->name, $name);
        $this->name = $name;
    }

    public function setZipcode($zipcode)
    {
        $this->propertyChanged('zipcode', $this->zipcode, $zipcode);
        $this->zipcode = (string) $zipcode;
    }

    public function setBouhId($bouhId)
    {
        $this->propertyChanged('bouhId', $this->bouhId, $bouhId);
        $this->bouhId = (string) $bouhId;
    }

    public function getId()
    {
        return $this->id;
    }

    public function getName()
    {
        return $this->name;
    }

    public function getZipcode()
    {
        return $this->zipcode;
    }

    public function getBouhId()
    {
        return $this->bouhId;
    }

    public function parksAre(array $parks)
    {
        $this->parks = $parks;
    }

    public function getParks()
    {
        return $this->parks;
    }

    public function setDepartment(Department $department)
    {
        $this->department = $department;
    }

    public function getDepartment()
    {
        return $this->department;
    }
}
