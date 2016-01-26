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

class Country implements NotifyPropertyInterface
{

    use NotifyProperty;

    protected $code      = null;
    protected $name      = null;
    protected $continent = null;
    protected $region    = null;
    protected $president = null;

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

    public function countryLanguageIs(CountryLanguage $countryLanguage)
    {
        $this->countryLanguage = $countryLanguage;
    }

}
