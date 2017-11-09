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

use sample\src\model\CountryLanguage;

/**
 * @Entity @Table(name="t_country_cou")
 **/
class Country
{
    /** @Id @Column(type="string", name="cou_code") **/
    protected $code      = null;

    /** @Column(type="string", name="cou_name") **/
    protected $name      = null;

    /** @Column(type="string", name="cou_continent") **/
    protected $continent = null;

    /** @Column(type="string", name="cou_region") **/
    protected $region    = null;

    /** @Column(type="string", name="cou_head_of_state") **/
    protected $president = null;

    /**
     * @OneToMany(targetEntity="CountryLanguage", mappedBy="country", fetch="EAGER")
     * @var CountryLanguage
     **/
    protected $countryLanguages = [];

    /**
     * @OneToMany(targetEntity="City", mappedBy="country", fetch="EAGER")
     */
    private $cities;

    public function __construct()
    {
        $this->cities = new ArrayCollection();
        $this->countryLanguages = new ArrayCollection();
    }

    public function setCode($code)
    {
        $this->code = (string) $code;
    }

    public function getCode()
    {
        return (string) $this->code;
    }

    public function setName($name)
    {
        $this->name = (string) $name;
    }

    public function getName()
    {
        return (string) $this->name;
    }

    public function setContinent($continent)
    {
        $this->continent = (string) $continent;
    }

    public function getContinent()
    {
        return (string) $this->continent;
    }

    public function setRegion($region)
    {
        $this->region = (string) $region;
    }

    public function getRegion()
    {
        return (string) $this->region;
    }

    public function setPresident($president)
    {
        $this->president = (string) $president;
    }

    public function getPresident()
    {
        return (string) $this->president;
    }

    public function countryLanguageIs(CountryLanguage $countryLanguage)
    {
        $this->countryLanguage = $countryLanguage;
    }

    public function countryLanguagesAre(array $countryLanguages)
    {
        $this->countryLanguages = $countryLanguages;
    }

    public function getCountryLanguages()
    {
        return $this->countryLanguages;
    }
}
