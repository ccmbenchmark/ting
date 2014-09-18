<?php

namespace sample\src;

// ting autoloader
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
        'host'      => 'localhost',
        'user'      => 'world_sample',
        'password'  => 'world_sample',
        'port'      => 3306,
    ],
];

$services = new \CCMBenchmark\Ting\Services();
$repositoriesNumber =
    $services
        ->get('MetadataRepository')
        ->batchLoadMetadata('sample\src\model', __DIR__ . '/model/*Repository.php');

$services->get('ConnectionPool')->setConfig($connections);
$unitOfWork = $services->get('UnitOfWork');

try {
    $cityRepository = new \sample\src\model\CityRepository($services);
    $city = $cityRepository->get(3);
    var_dump($city);

    echo str_repeat("-", 40) . "\n";
    $city->setName("boum");
    $city->setDistrict('Yolé');
    $unitOfWork->persist($city);

    $city2 = new model\City();
    $city2->setName('Bouh');
    $city2->setDistrict('Yo');
    $unitOfWork->persist($city2);

    $unitOfWork->flush();

    $city->setName("Herat");
    $city->setDistrict('Herat');
    $unitOfWork->persist($city);

    $city2->setName('Bouh 2');
    $unitOfWork->persist($city2);

    $unitOfWork->flush();

    $unitOfWork->remove($city2);
    $unitOfWork->flush();
} catch (Exception $e) {
    var_dump($e->getMessage());
}
