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

namespace CCMBenchmark\Ting\Logger;

interface CacheLoggerInterface
{
    public const OPERATION_GET    = 'GET';
    public const OPERATION_STORE  = 'STORE';
    public const OPERATION_DELETE = 'DELETE';
    public const OPERATION_EXIST  = 'EXIST';

    /**
     * Log an operation
     *
     * @param $operation string one of defined constant starting with OPERATION_
     * @param $keys array|string impacted keys by the operation
     * @return void
     */
    public function startOperation($operation, $keys);

    /**
     * Flag the previously operation as stopped. Useful for time logging.
     *
     * @param $miss boolean tells if the last get was a miss if it was a read operation
     * @return void
     */
    public function stopOperation($miss = false);
}
