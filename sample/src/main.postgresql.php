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

use sample\src\model\CityRepository;

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
        'namespace' => '\CCMBenchmark\Ting\Driver\Pgsql',
        'master'    => [
            'host'      => 'localhost',
            'user'      => 'world_sample',
            'password'  => 'world_sample',
            'port'      => 5432
        ]
    ],
];

$memcached = [
    'servers' => [
        ['host' => '127.0.0.1', 'port' => 11211]
    ],
    'options' => [
        \Memcached::OPT_LIBKETAMA_COMPATIBLE => true,
        //\Memcached::OPT_SERIALIZER           => \Memcached::SERIALIZER_IGBINARY
        \Memcached::OPT_SERIALIZER           => \Memcached::SERIALIZER_PHP,
        \Memcached::OPT_PREFIX_KEY           => 'sample-'
    ],
    'persistent_id' => 'ting.test'
];

$services->get('ConnectionPool')->setConfig($connections);
$services->get('Cache')->setConfig($memcached);

$services->get('Cache')->store('key', 'storedInCacheValue', 10);
echo 'Test cache : ' . $services->get('Cache')->get('key') . "\n";

try {
    $cityRepository = $services->get('RepositoryFactory')->get('\sample\src\model\CityRepository');
    var_dump($cityRepository->get(['cit_id' => 3]));
    echo str_repeat("-", 40) . "\n";

    $query = $cityRepository->getQuery(
        'select
            cit_id, cit_name, c.cou_code, cit_district, cit_population, last_modified,
            co.cou_code, cou_name, cou_continent, cou_region, cou_head_of_state
        from t_city_cit as c
        inner join t_country_cou as co on (c.cou_code = co.cou_code)
        where co.cou_code = :code limit 1'
    );

    $collection = $query->setParams(['code' => 'FRA'])->query();

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
    /**
     * @var $cityRepository CityRepository
     */
    $cityRepository = $services->get('RepositoryFactory')->get('\sample\src\model\CityRepository');

    echo str_repeat("-", 40) . "\n";

    $query = $cityRepository->getCachedPreparedQuery(
        'select
                cit_id, cit_name, c.cou_code, cit_district, cit_population, last_modified,
                co.cou_code, cou_name, cou_continent, cou_region, cou_head_of_state
            from t_city_cit as c
            inner join t_country_cou as co on (c.cou_code = co.cou_code)
            where co.cou_code = :code limit 1'
    );

    $query->setTtl(10)->setCacheKey('cit_FRA_pgsql');

    $collection = $query->setParams(['code' => 'FRA'])->query();
    echo 'From Cache : ' . (int) $collection->isFromCache() . "\n";

    foreach ($collection as $result) {
        var_dump($result);
        echo str_repeat("-", 40) . "\n";
    }
} catch (Exception $e) {
    var_dump($e->getMessage());
}
