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

use atoum;

class Ip extends atoum
{

    public function testSerializeThenUnSerializeShouldReturnOriginalValue()
    {
        $value = '127.0.0.1';
        $this
            ->if($serializer = new \CCMBenchmark\Ting\Serializer\Ip())
            ->string($serializer->unserialize($serializer->serialize($value)))
            ->isEqualTo($value)
        ;
    }

    public function testSerializeInvalidValueShouldRaiseException()
    {
        $this
            ->if($serializer = new \CCMBenchmark\Ting\Serializer\Ip())
            ->exception(function () use ($serializer): void {
                $serializer->serialize('badip');
            })
            ->isInstanceOf(\CCMBenchmark\Ting\Serializer\RuntimeException::class)
        ;
    }

    public function testNullValueShouldBeReturned()
    {
        $this
            ->if($serializer = new \CCMBenchmark\Ting\Serializer\Ip())
            ->variable($serializer->serialize(null))
            ->isNull()
            ->variable($serializer->unserialize(null))
            ->isNull()
        ;
    }
}
