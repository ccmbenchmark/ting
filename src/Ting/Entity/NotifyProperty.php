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
     * spl_object_id() of the entity the UnitOfWork managed. A copy (clone) keeps it: the
     * UnitOfWork then recognises the copy of a managed entity, and saving it updates the row,
     * as in Ting < 3.10. Written by the UnitOfWork, only valid for the current request.
     *
     * @internal
     */
    public ?int $tingOriginId = null;

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
        unset($properties['listeners'], $properties['tingOriginId']);

        return $properties;
    }

    /**
     * Serialized key of each property kept by __sleep(), per class.
     *
     * @var array<class-string, array{0: list<string>, 1: array<string, true>}>
     */
    private static $sleepKeysByClass = [];

    /**
     * Serializes the entity without its listeners, in the format PHP writes without
     * __serialize(): private properties are keyed "\0Class\0name", protected ones "\0*\0name".
     * PHP reads this format back natively (no __unserialize()), with serialize() and igbinary,
     * and so does every Ting version: a cache shared by two versions stays readable
     * (rolling deploy, rollback).
     * When the entity defines __sleep(), only the properties it lists are kept, as PHP
     * ignores __sleep() once __serialize() exists.
     */
    public function __serialize(): array
    {
        $properties = (array) $this;
        unset($properties["\0*\0listeners"], $properties["tingOriginId"]);

        if (!method_exists($this, '__sleep')) {
            return $properties;
        }

        // Keys resolved once per class, again only if __sleep() changes its answer
        $sleep = $this->__sleep();
        $cached = self::$sleepKeysByClass[static::class] ?? null;
        if ($cached === null || $cached[0] !== $sleep) {
            $cached = self::$sleepKeysByClass[static::class] = [$sleep, self::serializedKeysOf(static::class, $sleep)];
        }

        return array_intersect_key($properties, $cached[1]);
    }

    /**
     * Key under which (array) $object exposes each given property, parent classes included.
     *
     * @param list<string> $names
     * @return array<string, true>
     */
    private static function serializedKeysOf(string $class, array $names): array
    {
        $wanted = array_flip($names);
        $keys = [];
        for ($reflection = new \ReflectionClass($class); $reflection !== false; $reflection = $reflection->getParentClass()) {
            foreach ($reflection->getProperties() as $property) {
                $name = $property->getName();
                if ($property->isStatic() || !isset($wanted[$name])
                    || $property->getDeclaringClass()->getName() !== $reflection->getName()
                ) {
                    continue;
                }
                if ($property->isPrivate()) {
                    $keys["\0" . $reflection->getName() . "\0" . $name] = true;
                } elseif ($property->isProtected()) {
                    $keys["\0*\0" . $name] = true;
                } else {
                    $keys[$name] = true;
                }
            }
        }
        // Properties added at runtime are public
        foreach ($names as $name) {
            $keys[$name] = true;
        }

        return $keys;
    }
}
