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

namespace CCMBenchmark\Ting\Entity;

trait NotifyProperty
{
    protected $listeners = [];

    /**
     * Add an observer to the current object
     * @param PropertyListenerInterface $listener
     * @return void
     */
    public function addPropertyListener(PropertyListenerInterface $listener)
    {
        $this->listeners[] = $listener;
    }

    /**
     * Notify all observers with old and new values
     * @param $propertyName
     * @param $oldValue
     * @param $newValue
     */
    public function propertyChanged($propertyName, $oldValue, $newValue)
    {
        if ($oldValue === $newValue) {
            return;
        }

        foreach ($this->listeners as $listener) {
            $listener->propertyChanged($this, $propertyName, $oldValue, $newValue);
        }
    }

    public function __debugInfo(): ?array
    {
        $properties = get_object_vars($this);
        unset($properties['listeners']);

        return $properties;
    }

    public function __serialize(): array
    {
        $properties = get_object_vars($this);
        unset($properties['listeners']);

        return $properties;
    }
}
