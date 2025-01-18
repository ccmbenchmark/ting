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

class Ip implements SerializerInterface
{
    /**
     * @param mixed $toSerialize
     * @param array $options
     * @throws RuntimeException
     */
    public function serialize($toSerialize, array $options = []): ?int
    {
        if ($toSerialize === null) {
            return null;
        }

        $value = ip2long($toSerialize);

        if ($value === false) {
            throw new RuntimeException('IPv4 Internet network address is invalid');
        }

        return $value;
    }

    /**
     * @param mixed $serialized
     * @param array  $options
     * @throws RuntimeException
     */
    public function unserialize($serialized, array $options = []): null|string|bool
    {
        if ($serialized === null) {
            return null;
        }

        $value = long2ip($serialized);

        return $value;
    }
}
