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

namespace sample\src\model;

use CCMBenchmark\Ting\Entity\NotifyProperty;
use CCMBenchmark\Ting\Entity\NotifyPropertyInterface;

class Movie implements NotifyPropertyInterface
{
    use NotifyProperty;

    protected $id          = null;
    protected $name        = null;
    protected $actors      = [];

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

    public function getName($withUUID = false)
    {
        $append = '';

        if ($withUUID === true) {
            $append = " (" . $this->tingUUID . ")";
        }

        return (string) $this->name . $append;
    }

    public function actorsAre(array $actors)
    {
        foreach ($actors as $actor) {
            $this->actors[] = $actor; // clone $actor;
        }
    }

    public function getActors()
    {
        return $this->actors;
    }
}
