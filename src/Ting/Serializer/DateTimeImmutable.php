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

class DateTimeImmutable implements SerializerInterface
{
    /**
     * @var array
     * format => Always used for serialization. Used for unserialization only if unSerializeUseFormat is true
     * unSerializeUseFormat => if false, any valid datetime format is automatically used
     * @see http://php.net/manual/en/datetime.formats.compound.php
     */
    private static $defaultOptions = ['format' => \DateTimeInterface::ATOM, 'unSerializeUseFormat' => true];
    private static $deserialisationFormat = 'Y-m-d\TH:i:sO';

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

        if (($toSerialize instanceof \DateTimeImmutable) === false) {
            throw new RuntimeException(
                'Cannot convert this value to DateTimeImmutable. Type was : ' . \gettype($toSerialize) .
                '. Instance of DateTimeImmutable expected.'
            );
        }
        $options = array_merge(self::$defaultOptions, $options);
        return $toSerialize->format($options['format']);
    }

    /**
     * @param string $serialized
     * @param array  $options
     * @return \DateTimeImmutable|null
     * @throws RuntimeException
     */
    public function unserialize($serialized, array $options = []): ?\DateTimeImmutable
    {
        if ($serialized === null) {
            return null;
        }

        //$this->parseSerialized($serialized);
        $options = array_merge(self::$defaultOptions, $options);
        if ($options['unSerializeUseFormat'] === true) {
            $value = \DateTimeImmutable::createFromFormat($options['format'], $serialized);
            if ($value === false) {
                throw new RuntimeException('Cannot convert ' . $serialized . ' to DateTimeImmutable.');
            }
        } else {
            try {
                $value = new \DateTimeImmutable($serialized);
            } catch (\Exception $e) {
                throw new RuntimeException(
                    'Cannot convert ' . $serialized . ' to DateTimeImmutable. Error is : ' . $e->getMessage()
                );
            }
        }

        return $value;
    }
}
