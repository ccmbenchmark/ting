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

class Json extends atoum
{
    public function testSerializeShouldReturnJsonEncodedValue()
    {
        $this
            ->if($jsonSerializer = new \CCMBenchmark\Ting\Serializer\Json())
            ->string($jsonSerializer->serialize(['Bouh']))
                ->isIdenticalTo(json_encode(['Bouh']))
        ;
    }

    public function testSerializeShouldReturnJsonEncodedValueAndUsePassedOptions()
    {
        $this
            ->if($jsonSerializer = new \CCMBenchmark\Ting\Serializer\Json())
            ->string($jsonSerializer->serialize(['"Bouh"'], ['options' => JSON_HEX_QUOT]))
                ->isIdenticalTo(json_encode(['"Bouh"'], JSON_HEX_QUOT))
        ;
    }

    public function testSerializeShouldReturnJsonEncodedValueAndUsePassedDepthAndRaiseException()
    {
        $this
            ->if($jsonSerializer = new \CCMBenchmark\Ting\Serializer\Json())
            ->exception(function () use ($jsonSerializer) {
                $jsonSerializer->serialize(['Bouh' => ['subBouh' => ['subSubBouh']]], ['depth' => 2]);
            })
                ->isInstanceOf('CCMBenchmark\Ting\Serializer\RuntimeException');
        ;
    }

    public function testUnserializeShouldReturnJsonDecodedValue()
    {
        $encodedValue = json_encode(['Bouh']);
        $this
            ->if($jsonSerializer = new \CCMBenchmark\Ting\Serializer\Json())
            ->array($jsonSerializer->unserialize($encodedValue))
                ->isIdenticalTo(json_decode($encodedValue))
        ;
    }

    public function testUnserializeShouldReturnJsonEncodedValueAndUsePassedOptions()
    {
        $encodedValue = json_encode(['"Bouh"'], JSON_HEX_QUOT);
        $this
            ->if($jsonSerializer = new \CCMBenchmark\Ting\Serializer\Json())
            ->array($jsonSerializer->unserialize($encodedValue, ['options' => JSON_HEX_QUOT]))
                ->isIdenticalTo(json_decode($encodedValue, false, 512, JSON_HEX_QUOT))
        ;
    }

    public function testUnserializeShouldReturnJsonEncodedValueAndUsePassedDepth()
    {
        $encodedValue = json_encode(['Bouh' => ['subBouh']]);

        $this
            ->if($jsonSerializer = new \CCMBenchmark\Ting\Serializer\Json())
            ->array($jsonSerializer->unserialize($encodedValue, ['assoc' => true, 'depth' => 3]))
                ->isIdenticalTo(json_decode($encodedValue, true, 3));
        ;
    }

    public function testUnserializeShouldRaiseExceptionOnInvalidJson()
    {
        $this
            ->if($jsonSerializer = new \CCMBenchmark\Ting\Serializer\Json())
            ->exception(function () use ($jsonSerializer) {
                $jsonSerializer->unserialize('bouh');
            })
                ->isInstanceOf('CCMBenchmark\Ting\Serializer\RuntimeException');
        ;
    }

    public function testNullValueShouldReturnNull()
    {
        $this
            ->if($serializer = new \CCMBenchmark\Ting\Serializer\Json())
            ->variable($serializer->serialize(null))
                ->isNull()
            ->variable($serializer->unserialize(null))
                ->isNull()
        ;
    }

    public function testEmptyStringValueShouldReturnNull()
    {
        $this
            ->if($serializer = new \CCMBenchmark\Ting\Serializer\Json())
            ->variable($serializer->serialize(''))
                ->isIdenticalTo('""')
            ->variable($serializer->unserialize(''))
                ->isNull()
        ;
    }
}
