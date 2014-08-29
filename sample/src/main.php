<?php

namespace sample\src;

// fastorm autoloader
use fastorm\Exception;
use sample\src\model\City;

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
        'namespace' => '\fastorm\Driver\Mysqli',
        'host'      => 'localhost',
        'user'      => 'world_sample',
        'password'  => 'world_sample',
        'port'      => 3306
    ),
);

$poolConnection = \fastorm\ConnectionPool::getInstance(array(
    'connections' => $connections
));

echo 'City1'."\n";
try {
    $cityRepository = new \sample\src\model\CityRepository();

    var_dump($cityRepository->get(3));
    echo str_repeat("-", 40) . "\n";

    $collection = $cityRepository->execute(new \fastorm\Query\Query(
        "select * from T_CITY_CIT as c
        inner join T_COUNTRY_COU as co on (c.cou_code = co.cou_code)
        where co.cou_code = :code limit 3",
        array('code' => 'FRA')
    ))->hydrator(new \fastorm\Entity\Hydrator());

    foreach ($collection as $result) {
        var_dump($result);
        echo str_repeat("-", 40) . "\n";
    }
} catch (Exception $e) {
    var_dump($e->getMessage());
}

echo 'City2'."\n";
try {
    $cityRepository = new \sample\src\model\CityRepository();

    //var_dump($cityRepository->get(3));
    echo str_repeat("-", 40) . "\n";

    $collection = $cityRepository->executePrepared(new \fastorm\Query\PreparedQuery(
        "select * from T_CITY_CIT as c
        inner join T_COUNTRY_COU as co on (c.cou_code = co.cou_code)
        where co.cou_code = :code limit 3",
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
    $cityRepository = new \sample\src\model\CityRepository();
    $collection = $cityRepository->getZCountryWithLotsPopulation();

    foreach ($collection as $result) {
        var_dump($result);
        echo str_repeat("-", 40) . "\n";
    }
} catch (Exception $e) {
    var_dump($e->getMessage());
}


try {
    $cityRepository = new \sample\src\model\CityRepository();
    $nb = $cityRepository->getNumberOfCities();
    var_dump(['initial' => $nb->rewind()->current()]);
    $cityRepository->startTransaction();
        $cityRepository->executePrepared(new \fastorm\Query\PreparedQuery(
            "INSERT INTO T_CITY_CIT
            (cit_name, cit_population) VALUES
            (:name, :pop)",
            ['name' => 'BOUH_TEST', 'pop' => 25000]
        ));
    $cityRepository->rollback();
    $nb = $cityRepository->getNumberOfCities();
    var_dump(['apres' => $nb->rewind()->current()]);
} catch (Exception $e) {
    var_dump($e);
}
