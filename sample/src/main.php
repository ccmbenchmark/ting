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

// ting autoloader
use CCMBenchmark\Ting\ConnectionPoolInterface;
use CCMBenchmark\Ting\Exception;
use CCMBenchmark\Ting\Query\PreparedQuery;

require __DIR__ . '/../../vendor/autoload.php';
// sample autoloader
require __DIR__ . '/../vendor/autoload.php';

$services = new \CCMBenchmark\Ting\Services();
$repositoriesNumber =
    $services
        ->get('MetadataRepository')
        ->batchLoadMetadata('sample\src\model', __DIR__ . '/model/*Repository.php');

echo str_repeat("-", 40) . "\n";
echo 'Load Repositories: ' . $repositoriesNumber . "\n";
echo str_repeat("-", 40) . "\n";

$connections = [
    'main' => [
        'namespace' => '\CCMBenchmark\Ting\Driver\Mysqli',
        'master' => [
            'host'      => 'localhost',
            'user'      => 'world_sample',
            'password'  => 'world_sample',
            'port'      => 3306,
        ],
        'slaves' => [
            [
                'host'      => '127.0.0.1',
                'user'      => 'world_sample',
                'password'  => 'world_sample',
                'port'      => 3306,
            ],
            [
                'host'      => '127.0.1.1', // Loopback : used to have a different connection opened
                'user'      => 'world_sample',
                'password'  => 'world_sample',
                'port'      => 3306,
            ]
        ]
    ]
];

$services->get('ConnectionPool')->setConfig($connections);

echo 'City1'."\n";
try {
    $cityRepository = $services->get('RepositoryFactory')->get('\sample\src\model\CityRepository');

    var_dump($cityRepository->get(3));
    echo str_repeat("-", 40) . "\n";

    $collection = $cityRepository->execute(
        new \CCMBenchmark\Ting\Query\Query(
            "select * from t_city_cit as c
            inner join t_country_cou as co on (c.cou_code = co.cou_code)
            where co.cou_code = :code limit 3",
            ['code' => 'FRA']
        )
    );

    foreach ($collection as $result) {
        var_dump($result);
        echo str_repeat("-", 40) . "\n";
    }
} catch (Exception $e) {
    var_dump($e->getMessage());
}

echo 'City2'."\n";
try {
    $cityRepository = $services->get('RepositoryFactory')->get('\sample\src\model\CityRepository');

    var_dump($cityRepository->get(3));
    echo str_repeat("-", 40) . "\n";

    $collection = $cityRepository->executePrepared(new \CCMBenchmark\Ting\Query\PreparedQuery(
        "select * from t_city_cit as c
        inner join t_country_cou as co on (c.cou_code = co.cou_code)
        where co.cou_code = :code limit 3",
        ['code' => 'FRA']
    ));

    foreach ($collection as $result) {
        var_dump($result);
        echo str_repeat("-", 40) . "\n";
    }
} catch (Exception $e) {
    var_dump($e->getMessage());
}

echo 'City3'."\n";
try {
    $cityRepository = $services->get('RepositoryFactory')->get('\sample\src\model\CityRepository');

    $collection = $cityRepository->executePrepared(
        new \CCMBenchmark\Ting\Query\PreparedQuery(
            "select * from t_city_cit as c
            inner join t_country_cou as co on (c.cou_code = co.cou_code)
            where co.cou_code = :code limit 3",
            ['code' => 'FRA']
        ),
        null,
        ConnectionPoolInterface::CONNECTION_MASTER
    );

    foreach ($collection as $result) {
        var_dump($result);
        echo str_repeat("-", 40) . "\n";
    }
} catch (Exception $e) {
    var_dump($e->getMessage());
}

try {
    $cityRepository = $services->get('RepositoryFactory')->get('\sample\src\model\CityRepository');
    $collection = $cityRepository->getZCountryWithLotsPopulation();

    foreach ($collection as $result) {
        var_dump($result);
        echo str_repeat("-", 40) . "\n";
    }
} catch (Exception $e) {
    var_dump($e->getMessage());
}

try {
    $cityRepository = $services->get('RepositoryFactory')->get('\sample\src\model\CityRepository');
    $nb = $cityRepository->getNumberOfCities();
    var_dump(['initial' => $nb->rewind()->current()]);
    $cityRepository->startTransaction();
        $cityRepository->executePrepared(
            new PreparedQuery(
                "INSERT INTO t_city_cit
                    (cit_name, cit_population) VALUES
                    (:name, :pop)",
                ['name' => 'BOUH_TEST', 'pop' => 25000]
            )
        );
    $cityRepository->rollback();
    $nb = $cityRepository->getNumberOfCities();
    var_dump(['apres' => $nb->rewind()->current()]);
} catch (Exception $e) {
    var_dump($e);
}
