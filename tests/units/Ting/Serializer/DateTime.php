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

namespace tests\units\CCMBenchmark\Ting\Serializer;

use mageekguy\atoum;

class DateTime extends atoum
{

    public function testSerializeThenUnSerializeShouldReturnOriginalValue()
    {
        $datetime = new \DateTime('now');
        $this
            ->if($serializer = new \CCMBenchmark\Ting\Serializer\DateTime())
            ->object($serializer->unserialize($serializer->serialize($datetime)))
                ->isEqualTo($datetime)
        ;
    }

    public function testUnserializeInvalidValueShouldRaiseException()
    {
        $this
            ->if($serializer = new \CCMBenchmark\Ting\Serializer\DateTime())
            ->exception(function () use ($serializer) {
                $serializer->unserialize('1345-67-89 bouh');
            })
                ->isInstanceOf('CCMBenchmark\Ting\Serializer\RuntimeException')
        ;
    }

    public function testUnserializeAutoShouldWorkWithCommonFormat()
    {
        $this
            ->if($serializer = new \CCMBenchmark\Ting\Serializer\DateTime())
            ->object($serializer->unserialize('2009-10-20 17:43:15', ['unSerializeUseFormat' => false]))
            ->object($serializer->unserialize('2008-08-04 12:47:54.659698', ['unSerializeUseFormat' => false]))
            ->object($serializer->unserialize('2008-08-04 12:47', ['unSerializeUseFormat' => false]))
            ->object($serializer->unserialize('2008-08-04', ['unSerializeUseFormat' => false]));
    }

    public function testSerializeInvalidValueShouldRaiseException()
    {
        $this
            ->if($serializer = new \CCMBenchmark\Ting\Serializer\DateTime())
            ->exception(function () use ($serializer) {
                $serializer->serialize(new \StdClass());
            })
                ->isInstanceOf('CCMBenchmark\Ting\Serializer\RuntimeException')
        ;
    }

    public function testNullValueShouldBeReturned()
    {
        $this
            ->if($serializer = new \CCMBenchmark\Ting\Serializer\DateTime())
            ->variable($serializer->serialize(null))
                ->isNull()
            ->variable($serializer->unserialize(null))
                ->isNull()
        ;
    }
}
