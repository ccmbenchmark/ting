# Ting - DataMapper PHP

Ting est une implémentation simple du pattern DataMapper pour PHP. Il fonctionne avec MySQL et PostgreSQL, sous licence Apache-2.0.

Il a quelques particularités et choix de conception distinctifs :

* Implémentation 100% PHP (pas de PDO, pas de XML)
* Pas de couche d'abstraction : vous parlez directement le langage de votre SGBD
* Rapide, faible consommation mémoire
* Simple à utiliser, simple à étendre

Vous pouvez lire ces quelques exemples, ou consulter plus d'[exemples](sample/).

Si vous utilisez Symfony, voir [ting_bundle](https://gitlab.ccmbg.com/core/ting_bundle) pour la couche d'intégration (configuration YAML/attributs, Value Resolver, User Provider, etc.).

## Sommaire
- [Bootstrap](#bootstrap)
- [Déclarer une entité](#déclarer-une-entité)
- [Récupérer un objet par ID](#récupérer-un-objet-par-id)
- [Requête simple](#requête-simple)
- [Requête préparée](#requête-préparée)
- [QueryBuilder](#querybuilder-fourni-par-aurasqlquery)
- [Écriture : l'UnitOfWork](#écriture--lunitofwork)
- [Hydrators](#hydrators)
- [Serializers](#serializers)
- [Drivers](#drivers)

## Bootstrap

Tout part de `CCMBenchmark\Ting\Services`, un petit conteneur [Pimple](https://github.com/silex/Pimple) qui assemble `ConnectionPool`, `MetadataRepository`, `UnitOfWork`, `RepositoryFactory`, etc.

```php
<?php
$services = new \CCMBenchmark\Ting\Services();

// Charge tous les fichiers *Repository.php correspondant au motif et enregistre leur Metadata
$services
    ->get('MetadataRepository')
    ->batchLoadMetadata('sample\src\model', __DIR__ . '/model/*Repository.php');

// Configuration des connexions
$connections = [
    'main' => [
        'namespace' => '\CCMBenchmark\Ting\Driver\Mysqli', // ou \CCMBenchmark\Ting\Driver\Pgsql
        'master' => [
            'host'     => '127.0.0.1',
            'user'     => 'root',
            'password' => 'p455w0rd',
            'port'     => 3306,
        ],
        'slaves' => [ // optionnel, un slave est choisi au hasard et réutilisé pour la durée de vie du pool
            ['host' => '127.0.0.1', 'user' => 'root', 'password' => 'p455w0rd', 'port' => 3306],
        ],
    ],
];
$services->get('ConnectionPool')->setConfig($connections);

// Optionnel : options par base de données (ex: timezone)
$services->get('ConnectionPool')->setDatabaseOptions([
    'world' => ['timezone' => 'UTC+1'],
]);

$cityRepository = $services->get('RepositoryFactory')->get('\sample\src\model\CityRepository');
```

`pimple/pimple` est requis pour `Services` (`composer require pimple/pimple`) ; `ting` ne fait que le suggérer, pour rester optionnel si vous assemblez les composants vous-même.

## Déclarer une entité

Une entité est une simple classe PHP. Chaque propriété accessible en lecture/écriture qui doit être suivie par l'`UnitOfWork` (voir plus bas) doit implémenter `NotifyPropertyInterface`, généralement via le trait `NotifyProperty`, et appeler `propertyChanged()` dans chaque setter :

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

Le mapping entre une entité et sa table vit dans le **Repository**, qui doit implémenter `MetadataInitializer::initMetadata()` et retourner un `Metadata` décrivant la table et ses champs :

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
        $metadata->setConnectionName('main'); // doit correspondre à une clé de la config des connexions
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

`MetadataRepository::batchLoadMetadata()` scanne les fichiers correspondants, les charge (`require`), et appelle `initMetadata()` sur chaque classe implémentant `MetadataInitializer` pour enregistrer son `Metadata`.

> Sous Symfony, `ting_bundle` permet de déclarer ce mapping avec les attributs `#[Schema\Table]` / `#[Schema\Column]` directement sur l'entité, plutôt que d'écrire `initMetadata()` à la main.

## Récupérer un objet par ID

    <?php
    $cityRepository = $services->get('RepositoryFactory')->get('\sample\src\model\CityRepository');

    ## Récupérer une ville par son id :
    $city = $cityRepository->get(3);

## Requête simple

    <?php
    # Cette requête accepte la même syntaxe qu'une requête préparée, mais reste une requête classique
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

## Requête préparée

    <?php
    // Requête simple :
    $query = $cityRepository->getQuery('SQL Statement');
    // Requête préparée :
    $query = $cityRepository->getPreparedQuery('SQL Statement');

## QueryBuilder fourni par [aura/sqlquery](https://github.com/auraphp/Aura.SqlQuery)

    <?php
    $queryBuilder = $cityRepository->getQueryBuilder($cityRepository::QUERY_SELECT);
    $queryBuilder
        ->cols(['cit_id', 'cit_name as name'])
        ->from('t_city_cit');
    $query = $cityRepository->getQuery($queryBuilder->getStatement());

## Écriture : l'UnitOfWork

Ting suit les changements sur les entités gérées et regroupe les écritures via `UnitOfWork`, récupéré via `$services->get('UnitOfWork')`.

```php
<?php
$unitOfWork = $services->get('UnitOfWork');

$city = $cityRepository->get(3);
$city->setName('Boum');
$unitOfWork->pushSave($city); // marque une mise à jour (entité déjà connue/gérée)

$newCity = new \sample\src\model\City();
$newCity->setName('Bouh');
$unitOfWork->pushSave($newCity); // marque une insertion (entité nouvelle)

$unitOfWork->process(); // exécute tous les insert/update en attente sur la base

$unitOfWork->pushDelete($newCity);
$unitOfWork->process(); // exécute la suppression
```

Autres méthodes utiles : `isManaged()`, `isNew()`, `shouldBePersisted()`, `shouldBeRemoved()`, `detach()`/`detachAll()` (arrêter de suivre une entité). Les transactions se gèrent au niveau du repository : `$repository->startTransaction()` / `$repository->rollback()`.

## Hydrators

Une `Query` hydrate son jeu de résultats via un hydrator (`$query->query(new Collection($hydrator))`). Plusieurs hydrators sont disponibles via `$services`, selon la forme de données souhaitée :

* `Hydrator` (par défaut) : hydrate une `Collection` d'entités.
* `HydratorSingleObject` : hydrate une ligne en un seul objet agrégat, utile avec `mapAliasTo()` (mapper une colonne virtuelle/aliasée comme `COUNT(*)` ou `NOW()` vers un setter) et `mapObjectTo()` (mapper les colonnes d'une table jointe vers un objet lié).
* `HydratorAggregator` : agrège plusieurs lignes en groupes (ex : un parent avec une liste d'enfants), piloté par `callableIdIs()` / `callableDataIs()`.
* `HydratorRelational` : version déclarative de ce qui précède, via `CCMBenchmark\Ting\Repository\Hydrator\RelationOne` / `RelationMany` (avec `AggregateFrom`/`AggregateTo`) pour décrire des relations one-to-one et one-to-many entre les tables jointes.
* `HydratorArray` : hydrate les lignes sous forme de tableaux plutôt que d'entités.

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
    'countryIs' // setter appelé sur l'entité City
));

$collection = $query->query(new \CCMBenchmark\Ting\Repository\Collection($hydrator));
foreach ($collection as $city) {
    echo $city->getName() . ' - ' . $city->getCountry()->getName() . "\n";
}
```

Voir `sample/src/main.php` et `sample/src/mainUnitOfWork.php` pour des exemples complets et exécutables (requête simple, requête préparée, `UnitOfWork`, hydratation agrégée et relationnelle).

## Serializers

Les serializers convertissent une valeur PHP vers/depuis sa représentation en base, utilisés à la fois pour les champs d'entité (via le `type` de `Metadata::addField()`) et pour les colonnes virtuelles/aliasées (via `HydratorSingleObject::unserializeAliasWith()`). Disponibles dans `CCMBenchmark\Ting\Serializer` :

* `DateTime` / `DateTimeImmutable` / `DateTimeZone`
* `BackedEnum`
* `Uuid` (nécessite `symfony/uid`)
* `Ip`
* `Json`
* `Geometry` (nécessite `brick/geo`, pour les colonnes Geometry MariaDB)

## Drivers

Ting fournit des drivers pour :

* MySQL (`CCMBenchmark\Ting\Driver\Mysqli`, nécessite `ext-mysqli`)
* PostgreSQL (`CCMBenchmark\Ting\Driver\Pgsql`, nécessite `ext-pgsql`)
* SphinxQL (`CCMBenchmark\Ting\Driver\SphinxQL`, parle le protocole MySQL)

Le driver utilisé par une connexion est sélectionné via la clé `namespace` de sa config (voir [Bootstrap](#bootstrap)).

## Plus :
* [Issues](https://gitlab.ccmbg.com/core/ting/-/issues)
