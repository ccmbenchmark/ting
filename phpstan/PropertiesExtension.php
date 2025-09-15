<?php

namespace CCMBenchmark\Ting\PHPStan;

use CCMBenchmark\Ting\Util\PropertyAccessor;
use PHPStan\Reflection\ExtendedPropertyReflection;
use PHPStan\Rules\Properties\ReadWritePropertiesExtension;

Class PropertiesExtension implements ReadWritePropertiesExtension
{

    public function isAlwaysRead(ExtendedPropertyReflection $property, string $propertyName): bool
    {
        $className = $property->getDeclaringClass()->getName();

        if ($className == PropertyAccessor::class && $propertyName === 'writePropertyCache') {
            return true;
        }

        return false;
    }

    public function isAlwaysWritten(ExtendedPropertyReflection $property, string $propertyName): bool
    {
        return false;
    }

    public function isInitialized(ExtendedPropertyReflection $property, string $propertyName): bool
    {
        return false;
    }
}