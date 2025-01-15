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

namespace CCMBenchmark\Ting;

use CCMBenchmark\Ting\Driver\DriverInterface;
use CCMBenchmark\Ting\Logger\DriverLoggerInterface;

interface ConnectionPoolInterface
{
    /**
     * @param DriverLoggerInterface $logger
     */
    public function __construct(DriverLoggerInterface $logger);

    /**
     * @param array $config
     */
    public function setConfig($config);

    /**
     * @param string $name
     * @param string $database
     * @return DriverInterface
     * @throws Exception
     */
    public function master($name, $database);

    /**
     * @param string $name
     * @param string $database
     * @return DriverInterface
     * @throws Exception
     */
    public function slave($name, $database);

    public function closeAll();

    public function setDatabaseOptions($options): void;
}
