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
use CCMBenchmark\Ting\Exception;
use CCMBenchmark\Ting\Repository\Collection;
use CCMBenchmark\Ting\Repository\Hydrator;
use CCMBenchmark\Ting\Repository\HydratorSingleObject;
use CCMBenchmark\Ting\Serializer\DateTime;
use CCMBenchmark\Ting\Serializer\Json;
use ffmpeg_movie;
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
        'namespace' => '\CCMBenchmark\Ting\Driver\Mysqli',
        'master' => [
            'host'      => '127.0.0.1',
            'user'      => 'root',
            'password'  => 'p455w0rd',
            'port'      => 3306,
        ]
    ]
];

$services->get('ConnectionPool')->setConfig($connections);

$cityRepository = $services->get('RepositoryFactory')->get('\sample\src\model\ProducerRepository');


$query = $cityRepository->getQuery("
select t_city_cit.*, t_country_cou.*, t_countrylanguage_col.*
from t_city_cit
left join t_country_cou on t_country_cou.cou_code = t_city_cit.cou_code
left join t_countrylanguage_col on t_countrylanguage_col.cou_code = t_country_cou.cou_code
");

$hydrator = $services->get('HydratorAggregator');
$hydrator->callableIdIs(function ($result) {
    return $result['t_city_cit']->getId();
});
$hydrator->callableDataIs(function ($result) {
    return $result['t_countrylanguage_col'];
});

$collection = $query->query(new Collection($hydrator));
$withUUID = false;

foreach ($collection as $result) {
    echo "City: " . $result['t_city_cit']->getName($withUUID) . "\n";
    echo "\tCountry: " . $result['t_country_cou']->getName($withUUID) . "\n";
    foreach ($result['aggregate'] as $countryLanguage) {
        echo "\t\tLanguage: " . $countryLanguage->getLanguage($withUUID) . "\n";
    }
    echo str_repeat("-", 40) . "\n";
}
die;

$query = $cityRepository->getQuery("
select t_city_cit.*, t_country_cou.*, t_countrylanguage_col.*
from t_city_cit
left join t_country_cou on t_country_cou.cou_code = t_city_cit.cou_code
left join t_countrylanguage_col on t_countrylanguage_col.cou_code = t_country_cou.cou_code
");

$hydrator = $services->get('HydratorRelational');
$hydrator->addRelation((new Hydrator\RelationMany())->aggregate('t_countrylanguage_col', 'getLanguage')->to('t_country_cou', 'getCode')->setter('countryLanguagesAre'));
$hydrator->addRelation((new Hydrator\RelationOne())->aggregate('t_country_cou', 'getCode')->to('t_city_cit', 'getId')->setter('countryIs'));
$hydrator->callableFinalizeAggregate(function ($result) {
    return $result['t_city_cit'];
});

$withUUID = false;

$collection = $query->query(new Collection($hydrator));
foreach ($collection as $city) {
    echo "City: " . $city->getName($withUUID) . "\n";
    $country = $city->getCountry();
    echo "\tCountry: " . $country->getName($withUUID) . "\n";
    $countryLanguages = $country->getCountryLanguages();
    foreach ($countryLanguages as $countryLanguage) {
        echo "\t\tLanguage: " . $countryLanguage->getLanguage($withUUID) . "\n";
    }
    echo str_repeat("-", 40) . "\n";
}
die;



$query = $cityRepository->getQuery(
    "select producer.*, worker.*, movie.*, actor.*
from producer
left join work_for_producer on producer.id = work_for_producer.producer_id
left join worker on worker.id = work_for_producer.worker_id
left join produce_movie on producer.id = produce_movie.producer_id
left join movie on movie.id = produce_movie.movie_id
left join actor_in_movie on actor_in_movie.movie_id = movie.id
left join actor on actor.id = actor_in_movie.actor_id"
);

/**
 * producer(id)->hasMany->worker(id)
 * producer(id)->hasMany->movie(id)
 * movie(id)->hasMany->actor(id)
 */


$hydrator = $services->get('HydratorRelational');
$hydrator->aggregate('worker', 'getId', 'producer', 'getId', 'workersAre');
$hydrator->aggregate('movie', 'getId', 'producer', 'getId', 'moviesAre');
$hydrator->aggregate('actor', 'getId', 'movie', 'getId', 'actorsAre');
$hydrator->callableFinalizeAggregate(function ($result) {
    return $result['producer'];
});

$collection = $query->query(new Collection($hydrator));

foreach ($collection as $producer) {
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
die;

/**
 * @var $cityRepository CityRepository
 */
$cityRepository = $services->get('RepositoryFactory')->get('\sample\src\model\CityRepository');

$queryCached = $cityRepository->getCachedQuery(
    "select cit_id, cit_name, c.cou_code, cit_district, cit_population, last_modified,
                co.cou_code, cou_name, cou_continent, cou_region, cou_head_of_state
             from t_city_cit as c
            inner join t_country_cou as co on (c.cou_code = co.cou_code)
            where co.cou_code = :code limit 1"
);

$queryCached->setTtl(10)->setCacheKey('cityFRA');
$collection = $queryCached->setParams(['code' => 'FRA'])->query();
echo 'From Cache : ' . (int) $collection->isFromCache() . "\n";
foreach ($collection as $result) {
    var_dump($result);
    echo str_repeat("-", 40) . "\n";
}

echo 'City1'."\n";
try {
    $cityRepository = $services->get('RepositoryFactory')->get('\sample\src\model\CityRepository');

    var_dump($cityRepository->get(3));
    echo str_repeat("-", 40) . "\n";

    $query = $cityRepository->getQuery(
        "select cit_id, cit_name, c.cou_code, cit_district, cit_population, last_modified,
            co.cou_code, cou_name, cou_continent, cou_region, cou_head_of_state
        from t_city_cit as c
        inner join t_country_cou as co on (c.cou_code = co.cou_code)
        where co.cou_code = :code limit 3"
    );

    $collection = $query->setParams(['code' => 'FRA'])->query();

    foreach ($collection as $result) {
        var_dump($result);
        echo str_repeat("-", 40) . "\n";
    }

    $collection = $query->setParams(['code' => 'BEL'])->query();

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

    $preparedQuery = $cityRepository->getPreparedQuery(
        "select c.* from t_city_cit as c
        inner join t_country_cou as co on (c.cou_code = co.cou_code)
        where co.cou_code = :code limit 2"
    );

    $preparedQuery->prepareQuery();
    $collection = $preparedQuery->setParams(['code' => 'FRA'])->query();

    foreach ($collection as $result) {
        var_dump($result);
        echo str_repeat("-", 40) . "\n";
    }

    $collection2 = $preparedQuery->setParams(['code' => 'BEL'])->query();

    foreach ($collection2 as $result) {
        var_dump($result);
        echo str_repeat("-", 40) . "\n";
    }

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

    $query = $cityRepository->getQuery(
        "select
          c.*, SUM(1) as toto, NOW() as broum,
          co.cou_code, co.cou_name, cou_continent, cou_region, cou_head_of_state,
          col.cou_code, col.col_language, col_is_official, col_percentage
        from t_city_cit as c
        inner join t_country_cou as co on (c.cou_code = co.cou_code)
        inner join t_countrylanguage_col as col on (col.cou_code = co.cou_code)
        where co.cou_code = :code limit 3"
    );
    $query->selectMaster(true);

    $hydrator = $services->get('HydratorSingleObject');
    $hydrator
        ->mapAliasTo('broum', 'c', 'setBroum')
        ->mapAliasTo('toto', 'c', 'setTutu')
        ->mapObjectTo('co', 'c', 'countryIs')
        ->unserializeAliasWith('broum', new DateTime())
        ->mapObjectTo('col', 'co', 'countryLanguageIs');
    $collection = $query->setParams(['code' => 'FRA'])->query(new Collection($hydrator));

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
    var_dump(['initial' => $nb]);
    $cityRepository->startTransaction();
        $query = $cityRepository->getQuery(
            "INSERT INTO t_city_cit
                (cit_name, cit_population) VALUES
                (:name, :pop)"
        );

        $query->setParams(['name' => 'BOUH_TEST', 'pop' => 25000])->execute();
    $cityRepository->rollback();
    $nb = $cityRepository->getNumberOfCities();
    var_dump(['apres' => $nb]);
} catch (Exception $e) {
    var_dump($e);
}
