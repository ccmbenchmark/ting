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
     * @param \DateTime $toSerialize
     * @param array $options
     * @return int
     * @throws RuntimeException
     */
    public function serialize($toSerialize, array $options = [])
    {
        if ($toSerialize === null) {
            return null;
        }

        $value = ip2long($toSerialize);

        if ($value === false) {
            throw new RuntimeException('Pv4 Internet network address is invalid');
        }

        return $value;
    }

    /**
     * @param string $serialized
     * @param array  $options
     * @return string
     * @throws RuntimeException
     */
    public function unserialize($serialized, array $options = [])
    {
        if ($serialized === null) {
            return null;
        }

        $value = long2ip($serialized);

        return $value;
    }

}