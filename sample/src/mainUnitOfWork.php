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

$unitOfWork = \fastorm\UnitOfWork::getInstance();

try {
    $cityRepository = \sample\src\model\CityRepository::getInstance();
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
