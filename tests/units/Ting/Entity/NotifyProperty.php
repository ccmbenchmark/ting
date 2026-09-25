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
use tests\fixtures\model\Tree;

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

        var_dump($entity);

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

    public function testUnserializeShouldRestorePrivateProperties()
    {
        $this
            ->given($entity = unserialize(serialize(new Tree(5, 3))))
            ->integer($entity->getId())->isIdenticalTo(5)
            ->integer($entity->getParent())->isIdenticalTo(3)
        ;
    }

    /**
     * @extensions igbinary
     */
    public function testIgbinaryUnserializeShouldRestorePrivateProperties()
    {
        $this
            ->given($entity = igbinary_unserialize(igbinary_serialize(new Tree(5, 3))))
            ->integer($entity->getId())->isIdenticalTo(5)
            ->integer($entity->getParent())->isIdenticalTo(3)
        ;
    }

    /**
     * Donnees ecrites avant Ting 3.12, sans __serialize() : PHP nomme les proprietes
     * privees "\0Classe\0nom" et les protegees "\0*\0nom".
     */
    public function testUnserializeShouldReadDataSerializedWithoutSerializeMethod()
    {
        $class = Tree::class;
        $properties = [
            "\0*\0listeners" => 'a:0:{}',
            "\0" . $class . "\0id" => 'i:5;',
            "\0" . $class . "\0parent" => 'i:3;',
        ];
        $body = '';
        foreach ($properties as $name => $value) {
            $body .= sprintf('s:%d:"%s";%s', strlen($name), $name, $value);
        }
        $data = sprintf('O:%d:"%s":%d:{%s}', strlen($class), $class, count($properties), $body);

        $this
            ->given($entity = unserialize($data))
            ->integer($entity->getId())->isIdenticalTo(5)
            ->integer($entity->getParent())->isIdenticalTo(3)
        ;
    }

    public function testSerializeShouldKeepOnlyThePropertiesListedBySleep()
    {
        $this
            ->given($entity = new Tree(5, 3, new Tree(3)))
            ->array($entity->__serialize())
            ->isIdenticalTo(['id' => 5, 'parent' => 3])
            ->variable(unserialize(serialize($entity))->getHydratedParent())
            ->isNull()
        ;
    }

    public function testUnserializeShouldNotRestoreListeners()
    {
        $this
            ->given($entity = new Tree(5))
            ->and($entity->addPropertyListener(new \mock\CCMBenchmark\Ting\Entity\PropertyListenerInterface()))
            ->array((fn () => $this->listeners)->call(unserialize(serialize($entity))))
            ->isEmpty()
        ;
    }
}
