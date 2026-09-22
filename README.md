# Ting - PHP Datamapper

Ting is a simple DataMapper implementation for PHP. It runs with MySQL and PostgreSQL and is
under Apache-2.0 licence.

It has some distinctive features and design choices :

* Pure PHP implementation (no PDO, no XML)
* No abstraction layer : you speak the language of your RDBMS
* Fast, low memory consumption
* Simple to use, simple to extend

You can read this few examples, or see more [samples](sample/).

If you use Symfony, see [ting_bundle](https://gitlab.ccmbg.com/core/ting_bundle) for the integration layer (YAML/attributes configuration, Value Resolver, User Provider, etc.).

## Table of contents
- [Bootstrap](#bootstrap)
- [Declaring an entity](#declaring-an-entity)
- [Retrieve object by ID](#retrieve-object-by-id)
- [Simple query](#simple-query)
- [Prepared Statement](#prepared-statement)
- [QueryBuilder](#querybuilder-provided-by-aurasqlquery)
- [Writing: the UnitOfWork](#writing--the-unitofwork)
- [Hydrators](#hydrators)
- [Serializers](#serializers)
- [Drivers](#drivers)

## Bootstrap

Everything starts with `CCMBenchmark\Ting\Services`, a small [Pimple](https://github.com/silex/Pimple) container that wires together the `ConnectionPool`, `MetadataRepository`, `UnitOfWork`, `RepositoryFactory`, etc.

```php
<?php
$services = new \CCMBenchmark\Ting\Services();

// Load every *Repository.php file matching the pattern and register their Metadata
$services
    ->get('MetadataRepository')
    ->batchLoadMetadata('sample\src\model', __DIR__ . '/model/*Repository.php');

// Configure your connections
$connections = [
    'main' => [
        'namespace' => '\CCMBenchmark\Ting\Driver\Mysqli', // or \CCMBenchmark\Ting\Driver\Pgsql
        'master' => [
            'host'     => '127.0.0.1',
            'user'     => 'root',
            'password' => 'p455w0rd',
            'port'     => 3306,
        ],
        'slaves' => [ // optional, a random slave is picked and reused for the pool's lifetime
            ['host' => '127.0.0.1', 'user' => 'root', 'password' => 'p455w0rd', 'port' => 3306],
        ],
    ],
];
$services->get('ConnectionPool')->setConfig($connections);

// Optional: per-database options (e.g. timezone)
$services->get('ConnectionPool')->setDatabaseOptions([
    'world' => ['timezone' => 'UTC+1'],
]);

$cityRepository = $services->get('RepositoryFactory')->get('\sample\src\model\CityRepository');
```

`pimple/pimple` is required for `Services` (`composer require pimple/pimple`), it's only suggested by `ting` to keep it optional if you wire the components yourself.

## Declaring an entity

An entity is a plain PHP class. Every settable/gettable property that must be tracked for the `UnitOfWork` (see below) needs to implement `NotifyPropertyInterface`, usually via the `NotifyProperty` trait, and call `propertyChanged()` in every setter:

```php
<?php
namespace sample\src\model;

use CCMBenchmark\Ting\Entity\NotifyProperty;
use CCMBenchmark\Ting\Entity\NotifyPropertyInterface;

class City implements NotifyPropertyInterface
{
    use NotifyProperty;

    protected $id   = null;
    protected $name = null;

    public function setId($id)
    {
        $this->propertyChanged('id', $this->id, $id);
        $this->id = (int) $id;
    }

    public function getId()
    {
        return (int) $this->id;
    }

    public function setName($name)
    {
        $this->propertyChanged('name', $this->name, $name);
        $this->name = (string) $name;
    }

    public function getName()
    {
        return (string) $this->name;
    }
}
```

The mapping between an entity and its table lives in the **Repository**, which must implement `MetadataInitializer::initMetadata()` and return a `Metadata` describing the table and its fields:

```php
<?php
namespace sample\src\model;

use CCMBenchmark\Ting\Repository\Metadata;
use CCMBenchmark\Ting\Repository\MetadataInitializer;
use CCMBenchmark\Ting\Serializer\SerializerFactoryInterface;

class CityRepository extends \CCMBenchmark\Ting\Repository\Repository implements MetadataInitializer
{
    public static function initMetadata(SerializerFactoryInterface $serializerFactory, array $options = [])
    {
        $metadata = new Metadata($serializerFactory);

        $metadata->setEntity('sample\src\model\City');
        $metadata->setConnectionName('main'); // must match a key in the connections config
        $metadata->setDatabase('world');
        $metadata->setTable('t_city_cit');

        $metadata->addField([
            'primary'       => true,
            'autoincrement' => true,
            'fieldName'     => 'id',
            'columnName'    => 'cit_id',
            'type'          => 'int',
        ]);

        $metadata->addField([
            'fieldName'  => 'name',
            'columnName' => 'cit_name',
            'type'       => 'string',
        ]);

        return $metadata;
    }
}
```

`MetadataRepository::batchLoadMetadata()` scans matching files, requires them, and calls `initMetadata()` on every class implementing `MetadataInitializer` to register their `Metadata`.

> If you're on Symfony, `ting_bundle` lets you declare this mapping with `#[Schema\Table]` / `#[Schema\Column]` attributes directly on the entity instead of writing `initMetadata()` by hand.

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
    $query = $cityRepository->getQuery('SQL Statement');
    // Prepared statement :
    $query = $cityRepository->getPreparedQuery('SQL Statement');

## QueryBuilder provided by [aura/sqlquery](https://github.com/auraphp/Aura.SqlQuery)

    <?php
    $queryBuilder = $cityRepository->getQueryBuilder($cityRepository::QUERY_SELECT);
    $queryBuilder
        ->cols(['cit_id', 'cit_name as name'])
        ->from('t_city_cit');
    $query = $cityRepository->getQuery($queryBuilder->getStatement());

## Writing : the UnitOfWork

Ting tracks changes on managed entities and batches writes through `UnitOfWork`, retrieved from `$services->get('UnitOfWork')`.

```php
<?php
$unitOfWork = $services->get('UnitOfWork');

$city = $cityRepository->get(3);
$city->setName('Boum');
$unitOfWork->pushSave($city); // flags an update (entity already known/managed)

$newCity = new \sample\src\model\City();
$newCity->setName('Bouh');
$unitOfWork->pushSave($newCity); // flags an insert (entity is new)

$unitOfWork->process(); // executes all flagged inserts/updates against the database

$unitOfWork->pushDelete($newCity);
$unitOfWork->process(); // executes the delete
```

Other useful methods: `isManaged()`, `isNew()`, `shouldBePersisted()`, `shouldBeRemoved()`, `detach()`/`detachAll()` (stop tracking an entity). Transactions are handled at the repository level: `$repository->startTransaction()` / `$repository->rollback()`.

## Hydrators

A `Query` hydrates its result set through a hydrator (`$query->query(new Collection($hydrator))`). Several hydrators are available from `$services`, depending on the shape of data you need:

* `Hydrator` (default) : hydrates a `Collection` of entities.
* `HydratorSingleObject` : hydrates one row into one aggregate object, useful with `mapAliasTo()` (map a virtual/aliased column like `COUNT(*)` or `NOW()` to a setter) and `mapObjectTo()` (map a joined table's columns to a related object).
* `HydratorAggregator` : aggregates several rows into groups (e.g. a parent with a list of children), driven by `callableIdIs()` / `callableDataIs()`.
* `HydratorRelational` : declarative version of the above, using `CCMBenchmark\Ting\Repository\Hydrator\RelationOne` / `RelationMany` (with `AggregateFrom`/`AggregateTo`) to describe one-to-one and one-to-many relations between the joined tables.
* `HydratorArray` : hydrates rows as plain arrays instead of entities.

```php
<?php
$query = $cityRepository->getQuery("
    select t_city_cit.*, t_country_cou.*
    from t_city_cit
    left join t_country_cou on t_country_cou.cou_code = t_city_cit.cou_code
");

$hydrator = $services->get('HydratorRelational');
$hydrator->addRelation(new \CCMBenchmark\Ting\Repository\Hydrator\RelationOne(
    new \CCMBenchmark\Ting\Repository\Hydrator\AggregateFrom('t_country_cou', 'getCode'),
    new \CCMBenchmark\Ting\Repository\Hydrator\AggregateTo('t_city_cit', 'getId'),
    'countryIs' // setter called on the City entity
));

$collection = $query->query(new \CCMBenchmark\Ting\Repository\Collection($hydrator));
foreach ($collection as $city) {
    echo $city->getName() . ' - ' . $city->getCountry()->getName() . "\n";
}
```

See `sample/src/main.php` and `sample/src/mainUnitOfWork.php` for complete, runnable examples (simple query, prepared statement, `UnitOfWork`, aggregated and relational hydration).

## Serializers

Serializers convert a PHP value to/from its database representation, used both for entity fields (via `Metadata::addField()`'s `type`) and for virtual/aliased columns (via `HydratorSingleObject::unserializeAliasWith()`). Available in `CCMBenchmark\Ting\Serializer`:

* `DateTime` / `DateTimeImmutable` / `DateTimeZone`
* `BackedEnum`
* `Uuid` (requires `symfony/uid`)
* `Ip`
* `Json`
* `Geometry` (requires `brick/geo`, for MariaDB Geometry columns)

## Drivers

Ting ships drivers for:

* MySQL (`CCMBenchmark\Ting\Driver\Mysqli`, requires `ext-mysqli`)
* PostgreSQL (`CCMBenchmark\Ting\Driver\Pgsql`, requires `ext-pgsql`)
* SphinxQL (`CCMBenchmark\Ting\Driver\SphinxQL`, speaks the MySQL protocol)

The driver used by a connection is selected through the `namespace` key of its config (see [Bootstrap](#bootstrap)).

## More :
* [Issues](https://gitlab.ccmbg.com/core/ting/-/issues)
