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
 * @Entity @Table(name="t_countrylanguage_col")
 **/
class CountryLanguage
{
    /** @Id @Column(type="string", name="cou_code") **/
    protected $code       = null;

    /** @Id @Column(type="string", name="col_language") **/
    protected $language   = null;

    /** @Column(type="string", name="col_is_official") **/
    protected $isOfficial = null;

    /** @Column(type="string", name="col_percentage") **/
    protected $percentage = null;

    /**
     * @ManyToOne(targetEntity="Country", inversedBy="countryLanguages", fetch="EAGER")
     * @JoinColumn(name="cou_code", referencedColumnName="cou_code")
     */
    private $country;

    public function setCode($code)
    {
        $this->code = (string) $code;
    }

    public function getCode()
    {
        return (string) $this->code;
    }

    public function setLanguage($language)
    {
        $this->language = (string) $language;
    }

    public function getLanguage()
    {
        return (string) $this->language;
    }

    public function setIsOfficial($isOfficial)
    {
        $this->isOfficial = (string) $isOfficial;
    }

    public function getIsOfficial()
    {
        return (string) $this->isOfficial;
    }

    public function setPercentage($percentage)
    {
        $this->percentage = (string) $percentage;
    }

    public function getPercentage()
    {
        return (string) $this->percentage;
    }
}
