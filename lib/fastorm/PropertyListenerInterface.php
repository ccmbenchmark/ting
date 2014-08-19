<?php

namespace fastorm;

interface PropertyListenerInterface
{
    public function propertyChanged($entity, $propertyName, $oldValue, $newValue);
}
