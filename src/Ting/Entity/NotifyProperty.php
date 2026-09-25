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

    /**
     * Serializes the entity without its listeners, in the format PHP writes without
     * __serialize(): private properties are named "\0Class\0name", protected ones "\0*\0name".
     * Every Ting version reads this format back, with or without __unserialize(), which keeps
     * a cache shared by two versions readable (rolling deploy, rollback).
     * When the entity defines __sleep(), only the properties it lists are kept, as PHP
     * ignores __sleep() once __serialize() exists.
     */
    public function __serialize(): array
    {
        $properties = (array) $this;
        unset($properties["\0*\0listeners"]);

        if (method_exists($this, '__sleep')) {
            $kept = array_flip($this->__sleep());
            foreach (array_keys($properties) as $key) {
                if (!isset($kept[self::propertyNameFromSerializedKey($key)])) {
                    unset($properties[$key]);
                }
            }
        }

        return $properties;
    }

    /**
     * Restores each property in the class that declares it.
     * Reads both formats: "\0Class\0name" / "\0*\0name" (PHP default, Ting < 3.12) and
     * the plain "name" written by Ting 3.12 and 3.13. Without this method igbinary restores
     * plain names as public properties, and the private properties stay null.
     * Listeners are never restored from serialized data.
     */
    public function __unserialize(array $data): void
    {
        $byScope = [];
        foreach ($data as $key => $value) {
            $key = (string) $key;
            $property = self::propertyNameFromSerializedKey($key);
            if ($property === 'listeners') {
                continue;
            }
            $byScope[$this->declaringScope($key, $property)][$property] = $value;
        }

        foreach ($byScope as $scope => $properties) {
            \Closure::bind(function () use ($properties) {
                foreach ($properties as $property => $value) {
                    $this->$property = $value;
                }
            }, $this, $scope)();
        }
    }

    private static function propertyNameFromSerializedKey(string $key): string
    {
        $separator = strrpos($key, "\0");

        return $separator === false ? $key : substr($key, $separator + 1);
    }

    /**
     * Class in which a serialized property must be written back.
     */
    private function declaringScope(string $key, string $property): string
    {
        // "\0Class\0name" : private property of Class
        if ($key !== '' && $key[0] === "\0" && $key[1] !== '*') {
            return substr($key, 1, strrpos($key, "\0") - 1);
        }

        // "\0*\0name" or "name" : the class that declares the property, if any
        for ($class = new \ReflectionClass($this); $class !== false; $class = $class->getParentClass()) {
            if ($class->hasProperty($property)
                && $class->getProperty($property)->getDeclaringClass()->getName() === $class->getName()
            ) {
                return $class->getName();
            }
        }

        return static::class;
    }
}
