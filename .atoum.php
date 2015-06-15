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

/*
Sample atoum configuration file to have code coverage in html format and the treemap.
Do "php path/to/test/file -c path/to/this/file" or "php path/to/atoum/scripts/runner.php -c path/to/this/file -f path/to/test/file" to use it.
*/

use mageekguy\atoum;

/*$script
    ->addDefaultReport()
    ->addField($coverageHtmlField)
    //->addField($coverageTreemapField)
;*/

/*
 * Xunit report
 */
$xunit = new atoum\reports\asynchronous\xunit();
$runner->addReport($xunit);

/*
 * Xunit writer
 */
$writer = new atoum\writers\file('coverage/atoum.xunit.xml');
$xunit->addWriter($writer);

$clover = new atoum\reports\asynchronous\clover();
$runner->addReport($clover);

$cloverWriter = new atoum\writers\file('coverage/atoum.clover.xml');
$clover->addWriter($cloverWriter);
