<?php

namespace tests\units\CCMBenchmark\Ting\Util;

use atoum;
use tests\fixtures\model\HookedPropertiesEntity;
use tests\fixtures\model\PublicPropertiesEntity;

class PropertyAccessor extends \atoum
{
    /**
     * @php >= 8.4
     */
    public function testSetPropertyShouldBypassPropertyHook()
    {
        $accessor = new \CCMBenchmark\Ting\Util\PropertyAccessor();
        $entity = new HookedPropertiesEntity();
        $this
            ->if($accessor->setValue($entity, 'hookBoth', 'value', null))
                ->string($entity->hookBoth)
                    ->isIdenticalTo('value (hooked on get)')
        ;
    }
    
    public function testSetPropertyShouldRespectSetter()
    {

        $accessor = new \CCMBenchmark\Ting\Util\PropertyAccessor();
        $entity = new \mock\tests\fixtures\model\PublicPropertiesEntity();
        $this
            ->if($accessor->setValue($entity, 'propertyWithSetter', 'value', 'setPropertyWithSetter'))
            ->mock($entity)
                ->call('setPropertyWithSetter')
                    ->withArguments('value')
                        ->once()
        ;
    }
    
    public function testSetPropertyShouldFindSetter()
    {
        $accessor = new \CCMBenchmark\Ting\Util\PropertyAccessor();
        $entity = new \mock\tests\fixtures\model\PublicPropertiesEntity();
        $this
            ->if($accessor->setValue($entity, 'propertyWithSetter', 'value', null))
            ->mock($entity)
                ->call('setPropertyWithSetter')
                    ->withArguments('value')
                        ->once()
        ;
    }
    
    public function testGetPropertyShouldRespectGetter()
    {
        $accessor = new \CCMBenchmark\Ting\Util\PropertyAccessor();
        $entity = new \mock\tests\fixtures\model\PublicPropertiesEntity();
        $this
            ->if($accessor->getValue($entity, 'propertyWithGetter', 'getPropertyWithGetter'))
            ->mock($entity)
                ->call('getPropertyWithGetter')
                    ->withoutAnyArgument()
                        ->once()
        ;
    }
    
    public function testGetPropertyShouldFindGetter()
    {
        $accessor = new \CCMBenchmark\Ting\Util\PropertyAccessor();
        $entity = new \mock\tests\fixtures\model\PublicPropertiesEntity();
        $this
            ->if($accessor->getValue($entity, 'propertyWithGetter', null))
            ->mock($entity)
                ->call('getPropertyWithGetter')
                    ->withoutAnyArgument()
                        ->once()
        ;
    }
    
    public function testGetPropertyShouldThrowOnUnitializedProperty()
    {
        
        $accessor = new \CCMBenchmark\Ting\Util\PropertyAccessor();
        $entity = new \mock\tests\fixtures\model\PublicPropertiesEntity();
        $this
            ->exception(function () use ($accessor, $entity) {
                $accessor->getValue($entity, 'propertyWithSetter', null);
            })
                ->isInstanceOf(\Symfony\Component\PropertyAccess\Exception\UninitializedPropertyException::class)
        ;
    }
    
    public function testIsReadableShouldReturnTrueOnInitializedProperty()
    {
        $accessor = new \CCMBenchmark\Ting\Util\PropertyAccessor();
        $entity = new \mock\tests\fixtures\model\PublicPropertiesEntity();
        $this
            ->boolean($accessor->isReadable($entity, 'propertyWithDefaultValue', null))
                ->isTrue()
        ;
    }
    
    public function testIsReadableShouldReturnFalseOnUnitializedProperty()
    {
        $accessor = new \CCMBenchmark\Ting\Util\PropertyAccessor();
        $entity = new \mock\tests\fixtures\model\PublicPropertiesEntity();
        $this
            ->boolean($accessor->isReadable($entity, 'propertyWithoutSetter', null))
                ->isFalse()
        ;
    }
    
    public function testIsReadableShouldReturnTrueWhenAGetterIsDefined()
    {
        $accessor = new \CCMBenchmark\Ting\Util\PropertyAccessor();
        $entity = new \mock\tests\fixtures\model\PublicPropertiesEntity();
        $this
            ->boolean($accessor->isReadable($entity, 'propertyWithGetter', 'getPropertyWithGetter'))
                ->isTrue()
        ;
    }
    
    public function testIsReadableShouldReturnFalseWhenGetterDoesNotExists()
    {
        $accessor = new \CCMBenchmark\Ting\Util\PropertyAccessor();
        $entity = new \mock\tests\fixtures\model\PublicPropertiesEntity();
        $this
            ->boolean($accessor->isReadable($entity, 'propertyWithGetter', 'wrongGetterName'))
                ->isFalse()
        ;
    }
    
    public function testIsWritableShouldReturnTrueWhenASetterIsDefined()
    {
        $accessor = new \CCMBenchmark\Ting\Util\PropertyAccessor();
        $entity = new \mock\tests\fixtures\model\PublicPropertiesEntity();
        $this
            ->boolean($accessor->isWritable($entity, 'propertyWithSetter', 'setPropertyWithDefaultValue'))
                ->isTrue()
        ;
    }
    
    public function testIsWritableShouldReturnFalseWhenSetterDoesNotExists()
    {
        $accessor = new \CCMBenchmark\Ting\Util\PropertyAccessor();
        $entity = new \mock\tests\fixtures\model\PublicPropertiesEntity();
        $this
            ->boolean($accessor->isWritable($entity, 'propertyWithSetter', 'wrongSetterName'))
                ->isFalse()
        ;
    }
    
    public function testPropertyAccessorCanLeverageInternalCache()
    {
        $cache = new \mock\Symfony\Component\Cache\Adapter\ArrayAdapter();
        $accessor = new \CCMBenchmark\Ting\Util\PropertyAccessor();
        $accessor->setCacheItemPool($cache);
        $entity = new PublicPropertiesEntity();
        $this
            ->if($accessor->setValue($entity, 'propertyWithDefaultValue', 'value1', null))
            ->and($accessor->setValue($entity, 'propertyWithDefaultValue', 'value2', null))
            ->mock($cache)
                ->call('getItem')
                    ->withAnyArguments()
                        ->once()
        ;
    }
    
    public function testPropertyAccessorCanLeverageExternalCache()
    {
        $cache = new \mock\Symfony\Component\Cache\Adapter\ArrayAdapter();
        $accessorFirst = new \CCMBenchmark\Ting\Util\PropertyAccessor();
        $accessorFirst->setCacheItemPool($cache);
        $accessorSecond = new \CCMBenchmark\Ting\Util\PropertyAccessor();
        $accessorSecond->setCacheItemPool($cache);
        $entity = new PublicPropertiesEntity();
        $this
            ->if($accessorFirst->setValue($entity, 'propertyWithDefaultValue', 'value1', null))
            ->and($accessorSecond->setValue($entity, 'propertyWithDefaultValue', 'value2', null))
            ->mock($cache)
                ->call('getItem')
                    ->withAnyArguments()
                        ->twice()
        ;
    }
}