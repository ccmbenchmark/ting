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

class Json implements SerializerInterface
{

    const JSON_DEFAULT_DEPTH   = 512;
    const JSON_DEFAULT_OPTIONS = 0;

    /**
     * @param mixed $toSerialize
     * @param array $options
     * @return string
     */
    public function serialize($toSerialize, array $options = [])
    {
        if (isset($options['options']) === true) {
            $jsonOptions = $options['options'];
        } else {
            $jsonOptions = self::JSON_DEFAULT_OPTIONS;
        }

        if (isset($options['depth']) === true) {
            $jsonDepth = $options['depth'];
        } else {
            $jsonDepth = self::JSON_DEFAULT_DEPTH;
        }

        return json_encode($toSerialize, $jsonOptions, $jsonDepth);
    }

    /**
     * @param string $serialized
     * @param array $options
     * @return mixed
     */
    public function unserialize($serialized, array $options = [])
    {
        if (isset($options['assoc']) === true) {
            $jsonAssoc = $options['assoc'];
        } else {
            $jsonAssoc = false;
        }

        if (isset($options['depth']) === true) {
            $jsonDepth = $options['depth'];
        } else {
            $jsonDepth = self::JSON_DEFAULT_DEPTH;
        }

        if (isset($options['options']) === true) {
            $jsonOptions = $options['options'];
        } else {
            $jsonOptions = self::JSON_DEFAULT_OPTIONS;
        }

        return json_decode($serialized, $jsonAssoc, $jsonDepth, $jsonOptions);
    }
}
