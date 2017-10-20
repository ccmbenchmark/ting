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

namespace sample\src;

require __DIR__ . '/../../vendor/autoload.php';
// sample autoloader

use CCMBenchmark\Ting\Driver\Oracle\Driver;
use CCMBenchmark\Ting\Driver\QueryException;

try {
    $oracleDriver = new Driver();
    $oracleDriver->connect('localhost', 'system', 'oracle', 666);
    $oracleDriver->setDatabase('XE');

    $result = $oracleDriver->execute('DROP TABLE PLAYERS');
    if ($result === false) {
        echo "\nUnable to drop table";
        exit;
    }
    echo "\nTable dropped";

    $result = $oracleDriver->execute('CREATE TABLE PLAYERS (ID INT)');
    if ($result === false) {
        echo "\nUnable to create table";
        exit;
    }
    echo "\nTable created";

    $result = $oracleDriver->execute('DROP SEQUENCE players_seq');
    if ($result === false) {
        echo "\nUnable to drop sequence";
        exit;
    }
    echo "\nSequence dropped";

    $result = $oracleDriver->execute('CREATE SEQUENCE players_seq START WITH 1 INCREMENT BY 1 NOCACHE NOCYCLE');
    if ($result === false) {
        echo "\nUnable to create sequence";
        exit;
    }
    echo "\nSequence created";

    $result = $oracleDriver->execute('INSERT INTO PLAYERS VALUES(players_seq.nextval)');
    if ($result === false) {
        echo "\nUnable to insert into table";
        exit;
    }
    echo "\nInsert done";

    $result = $oracleDriver->execute(
    'SELECT ID FROM PLAYERS WHERE ID > :id AND ROWNUM <= :limit',
        [
            'id' => 0,
            'limit' => 5
        ]
    );
    if ($result === false) {
        echo "\nUnable to select";
        exit;
    }
    foreach ($result as $row) {
        var_dump($row);
    }

} catch (QueryException $exception) {
    echo "\n" . $exception->getMessage();
}

