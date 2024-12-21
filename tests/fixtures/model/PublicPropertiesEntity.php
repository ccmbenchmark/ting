<?php

namespace tests\fixtures\model;

class PublicPropertiesEntity
{
    public string $propertyWithSetter;
    
    public string $propertyWithoutSetter;
    
    public string $propertyWithDefaultValue = 'default';
    
    private string $propertyWithGetter = 'with getter';
    
    public function setPropertyWithSetter(string $propertyWithSetter)
    {
        
    }

    public function setPropertyWithDefaultValue(string $propertyWithDefaultValue): void
    {
        $this->propertyWithDefaultValue = $propertyWithDefaultValue;
    }

    public function getPropertyWithGetter(): string
    {
        return $this->propertyWithGetter;
    }
}