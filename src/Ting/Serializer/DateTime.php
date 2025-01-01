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

namespace CCMBenchmark\Ting\Serializer;

class DateTime implements SerializerInterface
{
    /**
     * @var array
     * format => Always used for serialization. Used for unserialization only if unSerializeUseFormat is true
     * unSerializeUseFormat => if false, any valid datetime format is automatically used
     * @see http://php.net/manual/en/datetime.formats.compound.php
     */
    private static $defaultOptions = ['format' => 'Y-m-d H:i:s', 'unSerializeUseFormat' => true];

    /**
     * @param \DateTime $toSerialize
     * @param array $options
     * @return string|null
     * @throws RuntimeException
     */
    public function serialize($toSerialize, array $options = []): ?string
    {
        if ($toSerialize === null) {
            return null;
        }

        if (($toSerialize instanceof \DateTime) === false) {
            throw new RuntimeException(
                'Cannot convert this value to datetime. Type was : ' . \gettype($toSerialize) .
                '. Instance of DateTime expected.'
            );
        }
        $options = array_merge(self::$defaultOptions, $options);
        return $toSerialize->format($options['format']);
    }

    /**
     * @param string $serialized
     * @param array  $options
     * @return \Datetime|null
     * @throws RuntimeException
     */
    public function unserialize($serialized, array $options = []): ?\DateTime
    {
        if ($serialized === null) {
            return null;
        }

        $options = array_merge(self::$defaultOptions, $options);
        if ($options['unSerializeUseFormat'] === true) {
            $value = \DateTime::createFromFormat($options['format'], $serialized);
            if ($value === false) {
                throw new RuntimeException('Cannot convert ' . $serialized . ' to datetime.');
            }
        } else {
            try {
                $value = new \DateTime($serialized);
            } catch (\Exception $e) {
                throw new RuntimeException(
                    'Cannot convert ' . $serialized . ' to datetime. Error is : ' . $e->getMessage()
                );
            }
        }

        return $value;
    }
}
