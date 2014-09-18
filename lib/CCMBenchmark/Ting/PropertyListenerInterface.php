<?php

namespace CCMBenchmark\Ting;

interface PropertyListenerInterface
{
    public function propertyChanged($entity, $propertyName, $oldValue, $newValue);
}
