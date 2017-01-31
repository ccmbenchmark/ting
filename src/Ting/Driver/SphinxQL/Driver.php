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

namespace CCMBenchmark\Ting\Driver\SphinxQL;

use CCMBenchmark\Ting\Driver\Mysqli;

class Driver extends Mysqli\Driver
{

    /**
     * Quote value according to the type of variable
     * @param mixed $value
     * @return string
     *
     * @internal
     */
    protected function quoteValue($value)
    {
        switch (gettype($value)) {
            case "integer":
                // integer and double doesn't need quotes
            case "double":
                return $value;
                break;
            default:
                return "'" . $this->connection->real_escape_string($value) . "'";
                break;
        }
    }

    /**
     * @param $field
     * @return string
     */
    public function escapeField($field)
    {
        return $field;
    }
}
