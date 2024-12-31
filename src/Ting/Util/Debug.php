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

class Debug
{
    /**
     * Dump Ting object
     *
     * @param mixed $var
     * @param int $maxDepth
     */
    public function dump($var, $maxDepth = 10)
    {
        var_dump($this->export($var, $maxDepth));
    }

    /**
     * Export Ting object
     *
     * @param mixed $var
     * @param int $maxDepth
     *
     * @return mixed
     */
    public function export($var, $maxDepth = 10)
    {
        $return = [];

        if ($maxDepth === 0) {
            if (\is_object($var) === true) {
                return $var::class;
            } elseif (\is_array($var) === true) {
                return 'Array(' . \count($var) . ')';
            }
        }

        if ($var instanceof \Traversable || \is_array($var) === true) {
            foreach ($var as $key => $subVar) {
                if ($subVar instanceof \Traversable || \is_array($subVar) === true) {
                    $return[$key] = $this->export($subVar, $maxDepth - 1);
                } elseif (\is_object($subVar) === true) {
                    $return[$key] = $this->clean($subVar, $maxDepth - 1);
                } else {
                    $return[$key] = $subVar;
                }
            }
        } elseif (is_object($var) === true) {
            $return = $this->clean($var, $maxDepth - 1);
        } else {
            $return = $var;
        }

        return $return;
    }

    /**
     * @param Object $object
     * @param int $maxDepth
     *
     * @return mixed
     */
    private function clean($object, $maxDepth)
    {
        if ($maxDepth === 0) {
            return $object::class;
        }

        $objectToBeCleaned = clone $object;
        $reflectionObject = new \ReflectionObject($objectToBeCleaned);

        if ($reflectionObject->hasProperty('listeners') === true) {
            $reflectionProperty = new \ReflectionProperty($objectToBeCleaned::class, 'listeners');
            $reflectionProperty->setAccessible(true);
            $reflectionProperty->setValue($objectToBeCleaned, null);
        }

        foreach ($reflectionObject->getProperties() as $reflectionProperty) {
            $reflectionProperty->setAccessible(true);
            $propertyValue = $reflectionProperty->getValue($objectToBeCleaned);
            $reflectionProperty->setValue($objectToBeCleaned, $this->export($propertyValue, $maxDepth - 1));
        }

        return $objectToBeCleaned;
    }
}
