<?php

namespace sample\src;

// fastorm autoloader
require __DIR__ . '/../../vendor/autoload.php';
// sample autoloader
require __DIR__ . '/../vendor/autoload.php';

$serviceLocator = new \fastorm\ServiceLocator();
$repositoriesNumber = $serviceLocator->get('MetadataRepository')->batchLoadMetadata('sample\src\model', __DIR__ . '/model/*Repository.php');

echo str_repeat("-", 40) . "\n";
echo 'Load Repositories: ' . $repositoriesNumber . "\n";
echo str_repeat("-", 40) . "\n";

$connections = [
    'main' => [
        'namespace' => '\fastorm\Driver\Pgsql',
        'host'      => 'localhost',
        'user'      => 'postgres',
        'password'  => 'p455w0rd',
        'port'      => 5432
    ],
];

$serviceLocator->get('ConnectionPool')->setConfig($connections);

try {
    $cityRepository = new \sample\src\model\CityRepository($serviceLocator);

    var_dump($cityRepository->get(3));
    echo str_repeat("-", 40) . "\n";

    $collection = $cityRepository->execute($serviceLocator->get('Query')
        ->setSql(
        'select
            cit_id, cit_name, c.cou_code, cit_district, cit_population,
            co.cou_code, cou_name, cou_continent, cou_region, cou_head_of_state
        from t_city_cit as c
        inner join t_country_cou as co on (c.cou_code = co.cou_code)
        where co.cou_code = :code limit 3')
        ->setParams(['code' => 'FRA'])
    )->hydrator(new \fastorm\Entity\Hydrator($serviceLocator));

    foreach ($collection as $result) {
        var_dump($result);
        echo str_repeat("-", 40) . "\n";
    }
} catch (Exception $e) {
    var_dump($e->getMessage());
}

try {
    $cityRepository = new \sample\src\model\CityRepository($serviceLocator);
    $collection = $cityRepository->getZCountryWithLotsPopulation();

    foreach ($collection as $result) {
        var_dump($result);
        echo str_repeat("-", 40) . "\n";
    }
} catch (Exception $e) {
    var_dump($e->getMessage());
}
