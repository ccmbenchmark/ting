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

class CountryLanguage implements NotifyPropertyInterface
{

    use NotifyProperty;

    protected $code       = null;
    protected $language   = null;
    protected $isOfficial = null;
    protected $percentage = null;

    public function setCode($code)
    {
        $this->propertyChanged('code', $this->code, $code);
        $this->code = (string) $code;
    }

    public function getCode()
    {
        return (string) $this->code;
    }

    public function setLanguage($language)
    {
        $this->propertyChanged('language', $this->language, $language);
        $this->language = (string) $language;
    }

    public function getLanguage()
    {
        return (string) $this->language;
    }

    public function setIsOfficial($isOfficial)
    {
        $this->propertyChanged('isOfficial', $this->isOfficial, $isOfficial);
        $this->isOfficial = (string) $isOfficial;
    }

    public function getIsOfficial()
    {
        return (string) $this->isOfficial;
    }

    public function setPercentage($percentage)
    {
        $this->propertyChanged('percentage', $this->percentage, $percentage);
        $this->percentage = (string) $percentage;
    }

    public function getPercentage()
    {
        return (string) $this->percentage;
    }
}
