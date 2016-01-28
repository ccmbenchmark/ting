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

if (defined('PGSQL_TUPLES_OK') === false) {
    define('PGSQL_TUPLES_OK', 2);
}

if (defined('PGSQL_NUM') === false) {
    define('PGSQL_NUM', 2);
}

require __DIR__ . '/../vendor/autoload.php';
require __DIR__ . '/fixtures/model/Bouh.php';
require __DIR__ . '/fixtures/model/BouhRepository.php';
require __DIR__ . '/fixtures/model/City.php';
require __DIR__ . '/fixtures/model/CityRepository.php';
require __DIR__ . '/fixtures/model/CitySecond.php';
require __DIR__ . '/fixtures/model/CitySecondRepository.php';
require __DIR__ . '/fixtures/FakeDriver/Driver.php';
require __DIR__ . '/fixtures/FakeDriver/MysqliResult.php';
require __DIR__ . '/fixtures/FakeLogger/FakeDriverLogger.php';
require __DIR__ . '/fixtures/FakeLogger/FakeCacheLogger.php';
