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

namespace tests\units\CCMBenchmark\Ting\Entity;

use atoum;
use tests\fixtures\model\Bouh;

class NotifyProperty extends atoum
{
    public function testPropertyChangedShouldNotCallPropertyChangedOnListeners()
    {

        $mockListener = new \mock\CCMBenchmark\Ting\Entity\PropertyListenerInterface();

        $this
            ->if($notifyProperty = new \mock\tests\fixtures\model\Bouh())
            ->and($notifyProperty->addPropertyListener($mockListener))
            ->then($notifyProperty->propertyChanged('Bouh', 'value', 'value'))
            ->mock($mockListener)
                ->call('propertyChanged')
                    ->never()
        ;
    }

    public function testPropertyChangedShouldCallPropertyChangedOnListeners()
    {

        $mockListener  = new \mock\CCMBenchmark\Ting\Entity\PropertyListenerInterface();
        $mockListener2 = new \mock\CCMBenchmark\Ting\Entity\PropertyListenerInterface();

        $this
            ->if($notifyProperty = new \mock\tests\fixtures\model\Bouh())
            ->and($notifyProperty->addPropertyListener($mockListener))
            ->and($notifyProperty->addPropertyListener($mockListener2))
            ->then($notifyProperty->propertyChanged('Bouh', 'value', 'newValue'))
            ->mock($mockListener)
                ->call('propertyChanged')
                    ->once()
            ->mock($mockListener2)
                ->call('propertyChanged')
                    ->once()
        ;
    }

    public function testSerializationWithoutListerners()
    {
        $mockListener  = new \mock\CCMBenchmark\Ting\Entity\PropertyListenerInterface();
        $mockListener2 = new \mock\CCMBenchmark\Ting\Entity\PropertyListenerInterface();
        $entity = new Bouh();
        $entity->setId(20);
        $entity->setName('Xavier');
        $entity->addPropertyListener($mockListener);
        $entity->addPropertyListener($mockListener2);

        $expected = [
            'id' => 20,
            'firstname' => null,
            'name' => 'Xavier',
            'enabled' => null,
            'price' => null,
            'roles' => ['USER'],
            'city' => null,
            'retrievedTime' => null,
            'originalCity' => null,
            'cities' => [],
        ];
        $this
            ->array($serialized = $entity->__serialize())
            ->isIdenticalTo($expected)
        ;
    }
}
