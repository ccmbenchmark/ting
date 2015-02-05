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
require __DIR__ . '/../../vendor/autoload.php';
// sample autoloader
require __DIR__ . '/../vendor/autoload.php';

$services = new \CCMBenchmark\Ting\Services();
$repositories =
    $services
        ->get('MetadataRepository')
        ->batchLoadMetadata('sample\src\model', __DIR__ . '/model/*Repository.php');

echo str_repeat("-", 40) . "\n";
echo 'Load Repositories: ' . count($repositories) . "\n";
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

$services = new \CCMBenchmark\Ting\Services();
$repositoriesNumber =
    $services
        ->get('MetadataRepository')
        ->batchLoadMetadata('sample\src\model', __DIR__ . '/model/*Repository.php');

$services->get('ConnectionPool')->setConfig($connections);
$unitOfWork = $services->get('UnitOfWork');

try {
    $cityRepository = $services->get('RepositoryFactory')->get('\sample\src\model\CityRepository');
    $city = $cityRepository->get(3);
    var_dump($city);

    echo str_repeat("-", 40) . "\n";
    $city->setName("boum");
    $city->setDistrict('Yolé');
    $unitOfWork->pushSave($city);

    $city2 = new model\City();
    $city2->setName('Bouh');
    $city2->setDistrict('Yo');
    $unitOfWork->pushSave($city2);

    $unitOfWork->process();

    $city->setName("Herat");
    $city->setDistrict('Herat');
    $unitOfWork->pushSave($city);

    $city2->setName('Bouh 2');
    $unitOfWork->pushSave($city2);

    $unitOfWork->process();

    $unitOfWork->pushDelete($city2);
    $unitOfWork->process();
} catch (Exception $e) {
    var_dump($e->getMessage());
}

try {
    $countryLanguageRepository =
        $services->get('RepositoryFactory')->get('\sample\src\model\CountryLanguageRepository');

    $countryLanguage = $countryLanguageRepository->get(['code' => 'AGO', 'language' => 'Kongo']);
    var_dump($countryLanguage);

} catch (Exception $e) {
    var_dump($e->getMessage());
}
