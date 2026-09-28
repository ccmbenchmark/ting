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
use tests\fixtures\model\TreeChildWithPrivate;
use tests\fixtures\model\TreeChildWithTrait;

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
            "\0*\0id" => 20,
            "\0*\0firstname" => null,
            "\0*\0name" => 'Xavier',
            "\0*\0enabled" => null,
            "\0*\0price" => null,
            "\0*\0roles" => ['USER'],
            "\0*\0city" => null,
            "\0*\0retrievedTime" => null,
            "\0*\0originalCity" => null,
            "\0*\0cities" => [],
        ];
        $this
            ->array($serialized = $entity->__serialize())
            ->isIdenticalTo($expected)
        ;
    }

    public function testDebugInfoShouldHideTheUnitOfWorkData()
    {
        $this
            ->given($entity = new Bouh())
            ->and($entity->tingOriginId = 12)
            ->array($entity->__debugInfo())
                ->notHasKeys(['listeners', 'tingOriginId'])
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
            ->isIdenticalTo(["\0" . Tree::class . "\0id" => 5, "\0" . Tree::class . "\0parent" => 3])
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

    /**
     * Le format ecrit est celui de PHP sans __serialize() : une version de Ting qui n'a pas
     * __unserialize() (3.9 a 3.13) relit l'entite, pendant un deploiement ou apres un retour arriere.
     */
    public function testSerializeShouldWriteThePhpDefaultFormat()
    {
        $this
            ->array((new Tree(5, 3))->__serialize())
            ->isIdenticalTo([
                "\0" . Tree::class . "\0id" => 5,
                "\0" . Tree::class . "\0parent" => 3,
            ])
        ;
    }

    /**
     * Donnees ecrites par Ting 3.12 et 3.13 : noms de proprietes sans prefixe.
     */
    public function testUnserializeShouldReadDataWrittenByTing312()
    {
        $class = Tree::class;
        $data = sprintf('O:%d:"%s":2:{s:2:"id";i:5;s:6:"parent";i:3;}', strlen($class), $class);

        $this
            ->given($entity = unserialize($data))
            ->integer($entity->getId())->isIdenticalTo(5)
            ->integer($entity->getParent())->isIdenticalTo(3)
        ;
    }

    /**
     * @dataProvider serializers
     */
    public function testUnserializeShouldRestorePrivatePropertiesOfAChildEntity(string $serialize, string $unserialize)
    {
        $this
            ->given($entity = $unserialize($serialize(new TreeChildWithPrivate(5, 'parent', 'child'))))
            ->integer($entity->getId())->isIdenticalTo(5)
            ->string($entity->getParentSecret())->isIdenticalTo('parent')
            ->string($entity->getChildSecret())->isIdenticalTo('child')
        ;
    }

    /**
     * @dataProvider serializers
     */
    public function testUnserializeShouldRestorePrivatePropertiesOfAParentClassWithoutTheTrait(string $serialize, string $unserialize)
    {
        $this
            ->given($entity = $unserialize($serialize(new TreeChildWithTrait(5, 'parent'))))
            ->integer($entity->getId())->isIdenticalTo(5)
            ->string($entity->getParentSecret())->isIdenticalTo('parent')
            ->array(get_object_vars($entity))->notHasKey('parentSecret')
        ;
    }

    protected function serializers(): array
    {
        $serializers = ['php' => ['serialize', 'unserialize']];
        if (extension_loaded('igbinary')) {
            $serializers['igbinary'] = ['igbinary_serialize', 'igbinary_unserialize'];
        }

        return $serializers;
    }
}
