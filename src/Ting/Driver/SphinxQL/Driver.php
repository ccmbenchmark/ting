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
     *
     * @internal
     */
    protected function quoteValue($value): int|float|string
    {
        return match (\gettype($value)) {
            "integer", "double" => $value,
            default => "'" . $this->connection->real_escape_string($value) . "'",
        };
    }

    public function escapeField(mixed $field = null): string
    {
        return $field;
    }
}
