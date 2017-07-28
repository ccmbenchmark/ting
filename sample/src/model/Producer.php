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


class Producer implements NotifyPropertyInterface
{

    use NotifyProperty;

    protected $id          = null;
    protected $name        = null;
    protected $movies      = [];
    protected $workers     = [];

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

    public function moviesAre(array $movies)
    {
        echo "producer->moviesAre()\n";
//        $this->movies = $movies;
//        return;
        foreach ($movies as $movie) {
            echo "movie " . utf8_encode($movie->getName()) . " have " . count($movie->getActors()) . " actors\n";
            $this->movies[] = $movie;
        }
    }

    public function getMovies()
    {
        return $this->movies;
    }
    public function workersAre(array $workers)
    {
        foreach ($workers as $worker) {
            $this->workers[] = clone $worker;
        }
    }

    public function getWorkers()
    {
        return $this->workers;
    }
}
