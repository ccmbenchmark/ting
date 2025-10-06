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
use tests\fixtures\ColorsEnum;

class BackedEnum extends atoum
{
    /**
     * @php 8.1
     */

    public function testSerializeThenUnSerializeShouldReturnOriginalValue()
    {
        $color = ColorsEnum::BLUE;
        $this
            ->if($serializer = new \CCMBenchmark\Ting\Serializer\BackedEnum())
            ->object($serializer->unserialize($serializer->serialize($color), ['enum' => ColorsEnum::class]))
                ->isEqualTo($color)
        ;
    }

    /**
     * @php 8.1
     */
    public function testUnserializeInvalidValueShouldRaiseException()
    {
        $this
            ->if($serializer = new \CCMBenchmark\Ting\Serializer\BackedEnum())
            ->exception(function () use ($serializer): void {
                $serializer->unserialize('1345-67-89 bouh', ['enum' => ColorsEnum::class]);
            })
                ->isInstanceOf(\CCMBenchmark\Ting\Serializer\RuntimeException::class)
        ;
    }
    /**
     * @php 8.1
     */
    public function testSerializeInvalidValueShouldRaiseException()
    {
        $this
            ->if($serializer = new \CCMBenchmark\Ting\Serializer\BackedEnum())
            ->exception(function () use ($serializer): void {
                $serializer->serialize(new \StdClass());
            })
                ->isInstanceOf(\CCMBenchmark\Ting\Serializer\RuntimeException::class)
        ;
    }

    /**
     * @php 8.1
     */
    public function testNullValueShouldBeReturned()
    {
        $this
            ->if($serializer = new \CCMBenchmark\Ting\Serializer\BackedEnum())
            ->variable($serializer->serialize(null))
                ->isNull()
            ->variable($serializer->unserialize(null, ['enum' => ColorsEnum::class]))
                ->isNull()
        ;
    }
}
