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

class DateTimeZone extends atoum
{
    public function testSerializeThenUnSerializeShouldReturnOriginalValue(): void
    {
        $datetime = new \DateTimeZone('Europe/Paris');
        $this
            ->if($serializer = new \CCMBenchmark\Ting\Serializer\DateTimeZone())
            ->object($serializer->unserialize($serializer->serialize($datetime)))
                ->isInstanceOf(\DateTimeZone::class)
            ->string($serializer->unserialize($serializer->serialize($datetime))->getName())
                ->isEqualTo('Europe/Paris')
        ;
    }

    public function testUnserializeInvalidValueShouldRaiseException(): void
    {
        $this
            ->if($serializer = new \CCMBenchmark\Ting\Serializer\DateTimeZone())
            ->exception(function () use ($serializer): void {
                $serializer->unserialize('Not a timezone');
            })
                ->isInstanceOf(\CCMBenchmark\Ting\Serializer\RuntimeException::class)
        ;
    }

    public function testSerializeInvalidValueShouldRaiseException(): void
    {
        $this
            ->if($serializer = new \CCMBenchmark\Ting\Serializer\DateTimeZone())
            ->exception(function () use ($serializer): void {
                $serializer->serialize(new \StdClass());
            })
                ->isInstanceOf(\CCMBenchmark\Ting\Serializer\RuntimeException::class)
        ;
    }

    public function testNullValueShouldBeReturned(): void
    {
        $this
            ->if($serializer = new \CCMBenchmark\Ting\Serializer\DateTimeZone())
            ->variable($serializer->serialize(null))
                ->isNull()
            ->variable($serializer->unserialize(null))
                ->isNull()
        ;
    }
}
