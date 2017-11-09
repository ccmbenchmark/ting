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

namespace sample\src\doctrineEntity;

/**
 * @Entity @Table(name="producer")
 **/
class Producer
{
    /** @Id @Column(type="integer") @GeneratedValue **/
    protected $id          = null;

    /** @Column(type="string") **/
    protected $name        = null;

    /**
     * @ManyToMany(targetEntity="Movie", fetch="EAGER")
     * @JoinTable(name="produce_movie",
     *     joinColumns={@JoinColumn(name="producer_id", referencedColumnName="id")},
     *     inverseJoinColumns={@JoinColumn(name="movie_id", referencedColumnName="id")}
     *     )
     * @var Actor[] An ArrayCollection of Actor objects.
     **/
    protected $movies      = [];

    /**
     * @ManyToMany(targetEntity="Worker", fetch="EAGER")
     * @JoinTable(name="work_for_producer",
     *     joinColumns={@JoinColumn(name="producer_id", referencedColumnName="id")},
     *     inverseJoinColumns={@JoinColumn(name="worker_id", referencedColumnName="id")}
     *     )
     * @var Actor[] An ArrayCollection of Actor objects.
     **/
    protected $workers     = [];

    public function getId()
    {
        return (int) $this->id;
    }

    public function getName()
    {
        return (string) $this->name;
    }

    public function getMovies()
    {
        return $this->movies;
    }

    public function getWorkers()
    {
        return $this->workers;
    }
}
