<?php

namespace sample\src;

// fastorm autoloader
use fastorm\Exception;
use sample\src\model\City;

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
        'namespace' => '\fastorm\Driver\Mysqli',
        'host'      => 'localhost',
        'user'      => 'world_sample',
        'password'  => 'world_sample',
        'port'      => 3306,
    ]
];

$serviceLocator->get('ConnectionPool')->setConfig($connections);

echo 'City1'."\n";
try {
    $cityRepository = new \sample\src\model\CityRepository($serviceLocator);

    var_dump($cityRepository->get(3));
    echo str_repeat("-", 40) . "\n";

    $collection = $cityRepository->execute(new \fastorm\Query\Query(
        "select * from t_city_cit as c
        inner join t_country_cou as co on (c.cou_code = co.cou_code)
        where co.cou_code = :code limit 3",
        array('code' => 'FRA')
    ))->hydrator(new \fastorm\Entity\Hydrator($serviceLocator));

    foreach ($collection as $result) {
        var_dump($result);
        echo str_repeat("-", 40) . "\n";
    }
} catch (Exception $e) {
    var_dump($e->getMessage());
}

echo 'City2'."\n";
try {
    $cityRepository = new \sample\src\model\CityRepository($serviceLocator);

    var_dump($cityRepository->get(3));
    echo str_repeat("-", 40) . "\n";

    $collection = $cityRepository->executePrepared(new \fastorm\Query\PreparedQuery(
        "select * from t_city_cit as c
        inner join t_country_cou as co on (c.cou_code = co.cou_code)
        where co.cou_code = :code limit 3",
        array('code' => 'FRA')
    ))->hydrator(new \fastorm\Entity\Hydrator($serviceLocator));

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

try {
    $cityRepository = new \sample\src\model\CityRepository($serviceLocator);
    $nb = $cityRepository->getNumberOfCities();
    var_dump(['initial' => $nb->rewind()->current()]);
    $cityRepository->startTransaction();
        $cityRepository->executePrepared(
            $serviceLocator->get('PreparedQuery')
                ->setSql(
                    "INSERT INTO t_city_cit
                    (cit_name, cit_population) VALUES
                    (:name, :pop)")
                ->setParams(['name' => 'BOUH_TEST', 'pop' => 25000])
        );
    $cityRepository->rollback();
    $nb = $cityRepository->getNumberOfCities();
    var_dump(['apres' => $nb->rewind()->current()]);
} catch (Exception $e) {
    var_dump($e);
}
