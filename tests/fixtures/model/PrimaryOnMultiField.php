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

class PrimaryOnMultiField implements NotifyPropertyInterface
{

    use NotifyProperty;

    protected $cityId;
    protected $otherItemId;
    protected $value;

    public function setCityId($cityId)
    {
        $this->propertyChanged('cityId', $this->cityId, $cityId);
        $this->cityId = $cityId;
    }

    public function getCityId()
    {
        return $this->cityId;
    }

    public function setOtherItemId($itemId)
    {
        $this->propertyChanged('otherItemId', $this->otherItemId, $itemId);
        $this->otherItemId = $itemId;
    }

    public function getOtherItemId()
    {
        return $this->otherItemId;
    }

    public function setValue($value)
    {
        $this->propertyChanged('value', $this->value, $value);
        $this->value = $value;
    }

    public function getValue()
    {
        return $this->value;
    }

}
