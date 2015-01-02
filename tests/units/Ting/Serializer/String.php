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

class String extends atoum{

    public function testSerializeThenUnSerializeShouldReturnOriginalValue()
    {
        $myString = 'Lorem ipsum dolor sit amet, consectetur adipiscing elit. Donec ullamcorper, nulla sit amet auctor
        rhoncus, orci nisi varius erat, a congue eros turpis non lorem.';

        $this
            ->if($serializer = new \CCMBenchmark\Ting\Serializer\String())
            ->string($serializer->unserialize($serializer->serialize($myString)))
                ->isIdenticalTo($myString)
        ;
    }
}