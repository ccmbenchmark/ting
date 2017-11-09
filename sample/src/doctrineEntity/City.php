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

use sample\src\model\Country;

/**
 * @Entity @Table(name="t_city_cit")
 **/
class City
{
    /** @Id @Column(type="integer", name="cit_id") @GeneratedValue **/
    protected $id          = null;

    /** @Column(type="string", name="cit_name") **/
    protected $name        = null;

    /** @Column(type="string", name="cou_code") **/
    protected $countryCode = null;

    /** @Column(type="string", name="cit_district") **/
    protected $district    = null;

    /** @Column(type="string", name="cit_population") **/
    protected $population  = null;

    /** @Column(type="datetime", name="last_modified") **/
    protected $dt          = null;

    /**
     * @ManyToOne(targetEntity="Country", inversedBy="cities", fetch="EAGER")
     * @JoinColumn(name="cou_code", referencedColumnName="cou_code")
     * @var Country
     **/
    protected $country     = null;

    public function setId($id)
    {
        $this->id = (int) $id;
    }

    public function getId()
    {
        return (int) $this->id;
    }

    public function setName($name)
    {
        $this->name = (string) $name;
    }

    public function getName()
    {
        return (string) $this->name;
    }

    public function setCountryCode($countryCode)
    {
        $this->countryCode = (string) $countryCode;
    }

    public function getCountryCode()
    {
        return (string) $this->countryCode;
    }

    public function setDistrict($district)
    {
        $this->district = (string) $district;
    }

    public function getDistrict()
    {
        return (string) $this->district;
    }

    public function setPopulation($population)
    {
        $this->population = (int) $population;
    }

    public function getPopulation()
    {
        return (int) $this->population;
    }

    public function setDt(\DateTime $dt = null)
    {
        $this->dt = $dt;
    }

    public function getDt()
    {
        if (is_object($this->dt) === true) {
            return clone $this->dt;
        }

        return $this->dt;
    }

    public function setTutu($value)
    {
        $this->tutu = $value;
    }

    public function setBroum($value)
    {
        $this->broum = $value;
    }

    public function countryIs(array $country)
    {
        $this->country = $country;
    }

    public function getCountry()
    {
        return $this->country;
    }
}
