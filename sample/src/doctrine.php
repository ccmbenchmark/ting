<?php

require __DIR__ . '/../../vendor/autoload.php';
require __DIR__ . '/../vendor/autoload.php';

use Doctrine\ORM\Tools\Setup;
use Doctrine\ORM\EntityManager;

$isDevMode = true;
$config = Setup::createAnnotationMetadataConfiguration(array(__DIR__ . "/doctrineEntity"), $isDevMode);

// database configuration parameters
$conn = array(
    'driver' => 'mysqli',
    'host' => '127.0.0.1',
    'user' => 'root',
    'password' => 'p455w0rd',
    'dbname' => 'world'
);

// obtaining the entity manager
$entityManager = EntityManager::create($conn, $config);
$logger = new \Doctrine\DBAL\Logging\DebugStack();
$entityManager->getConfiguration()->setSQLLogger($logger);

//$cityRepository = $entityManager->getRepository('\sample\src\doctrineEntity\City');
//$cities = $cityRepository->findAll();

$query = $entityManager->createQuery('
SELECT c FROM \sample\src\doctrineEntity\City as c
JOIN c.country as country
JOIN country.countryLanguages
');

/*
$query = $entityManager->createQuery('
SELECT c FROM \sample\src\doctrineEntity\City as c
');
*/

$cities = $query->getResult();

foreach ($cities as $city) {
    echo "City: " . $city->getName() . "\n";
    $country = $city->getCountry();
    echo "\tCountry: " . $country->getName() . "\n";
    $countryLanguages = $country->getCountryLanguages();
    foreach ($countryLanguages as $countryLanguage) {
        echo "\t\tLanguage: " . $countryLanguage->getLanguage() . "\n";
    }
    echo str_repeat("-", 40) . "\n";
}

echo count($logger->queries) . " requêtes\n";
foreach ($logger->queries as $query) {
    echo $query['sql'] . "\n";
}
die;


$producerRepository = $entityManager->getRepository('\sample\src\doctrineEntity\Producer');
$producers = $producerRepository->findAll();

foreach ($producers as $producer) {
    echo "Producer: " . $producer->getName() . "\n";
    $workers = $producer->getWorkers();
    foreach ($workers as $worker) {
        echo "\tWorker: " . $worker->getName() . "\n";
    }
    $movies = $producer->getMovies();
    foreach ($movies as $movie) {
        echo "\tMovie: " . utf8_encode($movie->getName()) . "\n";
        $actors = $movie->getActors();
        foreach ($actors as $actor) {
            echo "\t\tActor: " . $actor->getName() . "\n";
        }
    }
    echo str_repeat("-", 40) . "\n";
}

echo count($logger->queries) . " requêtes\n";
foreach ($logger->queries as $query) {
    echo $query['sql'] . "\n";
}
