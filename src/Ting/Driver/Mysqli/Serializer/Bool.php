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

namespace CCMBenchmark\Ting\Driver\Mysqli\Serializer;

use CCMBenchmark\Ting\Serializer\SerializerInterface;

class Bool implements SerializerInterface
{
    /**
     * @param mixed $toSerialize
     * @param array $options
     * @return int|null
     */
    public function serialize($toSerialize, array $options = [])
    {
        if ($toSerialize === true) {
            return 1;
        }
        if ($toSerialize === false) {
            return 0;
        }

        return null;
    }

    /**
     * @param string $serialized
     * @param array  $options
     * @return bool|null
     */
    public function unserialize($serialized, array $options = [])
    {
        if ($serialized === 1 || $serialized === '1') {
            return true;
        }
        if ($serialized === 0 || $serialized === '0') {
            return false;
        }

        return null;
    }
}
