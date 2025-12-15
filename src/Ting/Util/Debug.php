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

namespace CCMBenchmark\Ting\Util;

use ReflectionObject;
use ReflectionProperty;

class Debug
{
    /**
     * Dump Ting object
     */
    public function dump(mixed $var, int $maxDepth = 10): void
    {
        var_dump($this->export($var, $maxDepth));
    }

    /**
     * Export Ting object
     */
    public function export(mixed $var, int $maxDepth = 10): mixed
    {
        $return = [];

        if ($maxDepth === 0) {
            if (\is_object($var)) {
                return $var::class;
            }
            if (\is_array($var)) {
                return 'Array(' . \count($var) . ')';
            }
        }

        if (is_iterable($var)) {
            foreach ($var as $key => $subVar) {
                if (is_iterable($subVar)) {
                    $return[$key] = $this->export($subVar, $maxDepth - 1);
                } elseif (\is_object($subVar)) {
                    $return[$key] = $this->clean($subVar, $maxDepth - 1);
                } else {
                    $return[$key] = $subVar;
                }
            }
        } elseif (is_object($var)) {
            $return = $this->clean($var, $maxDepth - 1);
        } else {
            $return = $var;
        }

        return $return;
    }

    private function clean(object $object, int $maxDepth): mixed
    {
        if ($maxDepth === 0) {
            return $object::class;
        }

        $objectToBeCleaned = clone $object;
        $reflectionObject = new ReflectionObject($objectToBeCleaned);

        if ($reflectionObject->hasProperty('listeners')) {
            $reflectionProperty = new ReflectionProperty($objectToBeCleaned::class, 'listeners');
            $reflectionProperty->setValue($objectToBeCleaned, null);
        }

        foreach ($reflectionObject->getProperties() as $reflectionProperty) {
            $propertyValue = $reflectionProperty->getValue($objectToBeCleaned);
            $reflectionProperty->setValue($objectToBeCleaned, $this->export($propertyValue, $maxDepth - 1));
        }

        return $objectToBeCleaned;
    }
}
