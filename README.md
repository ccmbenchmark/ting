# Ting - PHP Datamapper

Ting is a simple DataMapper implementation for PHP. It runs with MySQL and PostgreSQL and is
under Apache-2.0 licence.

It has some distinctive features and design choices :

* Pure PHP implementation (no PDO, no XML)
* No abstraction layer : you speak the language of your RDBMS
* Fast, low memory consumption
* Simple to use, simple to extend

You can read this few examples, or go to the [Documentation](http://tech.ccmbg.com/ting/doc/en/index.html)
or see more [samples](https://bitbucket.org/ccmbenchmark/ting/src/).

## Retrieve object by ID

    <?php
    $cityRepository = $services->get('RepositoryFactory')->get('\sample\src\model\CityRepository');

    ## Retrieve city by id :
    $city = $cityRepository->get(3);

## Simple query

    <?php
    # This query supports the same syntax as prepared statements, but it'll be a regular query
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

## Prepared Statement

    <?php
    // Simple query :
    $query = $cityRepository->getQuery('/*SQL Statement*/');"
    // Prepared statement :
    $query = $cityRepository->getPreparedQuery('/*SQL Statement*/');"

## More :
* [Documentation](http://tech.ccmbg.com/ting/doc/en/index.html)
* [Issues](https://bitbucket.org/ccmbenchmark/ting/issues)