<?php

namespace sample\src;

// fastorm autoloader
require __DIR__ . '/../../vendor/autoload.php';
// sample autoloader
require __DIR__ . '/../vendor/autoload.php';

$metadataRepository = \fastorm\Entity\MetadataRepository::getInstance();
$repositoriesNumber = $metadataRepository->batchLoadMetadata('sample\src\model', __DIR__ . '/model/*Repository.php');

echo str_repeat("-", 40) . "\n";
echo 'Load Repositories: ' . $repositoriesNumber . "\n";
echo str_repeat("-", 40) . "\n";

$connections = array(
    'main' => array(
        'namespace' => '\fastorm\Driver\Pgsql',
        'host'      => 'localhost',
        'user'      => 'postgres',
        'password'  => 'postgres',
        'port'      => 5432
    ),
);

$poolConnection = \fastorm\ConnectionPool::getInstance(array(
    'connections' => $connections
));


try {
    $cityRepository = \sample\src\model\CityRepository::getInstance();

    //var_dump($cityRepository->get(3));
    echo str_repeat("-", 40) . "\n";

    $collection = $cityRepository->execute(new \fastorm\Query(
        'select
            cit_id, cit_name, c.cou_code, cit_district, cit_population,
            co.cou_code, cou_name, cou_continent, cou_region, cou_head_of_state
        from t_city_cit as c
        inner join t_country_cou as co on (c.cou_code = co.cou_code)
        where co.cou_code = :code limit 3',
        array('code' => 'FRA')
    ))->hydrator(new \fastorm\Entity\Hydrator());

    foreach ($collection as $result) {
        var_dump($result);
        echo str_repeat("-", 40) . "\n";
    }
} catch (Exception $e) {
    var_dump($e->getMessage());
}

try {
    $cityRepository = \sample\src\model\CityRepository::getInstance();
    $collection = $cityRepository->getZCountryWithLotsPopulation();

    foreach ($collection as $result) {
        var_dump($result);
        echo str_repeat("-", 40) . "\n";
    }
} catch (Exception $e) {
    var_dump($e->getMessage());
}
