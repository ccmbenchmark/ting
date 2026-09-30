# Démarrage rapide

## Installation

```bash
composer require ccmbenchmark/ting
```

Prérequis : PHP >= 8.0, et l'extension `ext-mysqli` et/ou `ext-pgsql` selon la base utilisée.

## Le point d'entrée : `Services`

Tout part de `CCMBenchmark\Ting\Services`, un conteneur qui expose les briques principales de Ting : `ConnectionPool`, `MetadataRepository`, `UnitOfWork`, `RepositoryFactory`, les hydrateurs, etc.

```php
$services = new \CCMBenchmark\Ting\Services();
```

## Charger les métadonnées

Chaque entité a un `Repository` associé qui décrit son mapping (voir [Repository et Metadata](03-repository-et-metadata.md)). `batchLoadMetadata` scanne un dossier à la recherche de fichiers `*Repository.php` et les charge :

```php
$repositories = $services
    ->get('MetadataRepository')
    ->batchLoadMetadata('sample\src\model', __DIR__ . '/model/*Repository.php');
```

## Configurer les connexions

Une connexion nommée (`main` ci-dessous) peut avoir un `master` (écriture) et des `slaves` (lecture, optionnel) :

```php
$connections = [
    'main' => [
        'namespace' => '\CCMBenchmark\Ting\Driver\Mysqli',
        'master' => [
            'host'     => '127.0.0.1',
            'user'     => 'root',
            'password' => 'p455w0rd',
            'port'     => 3306,
        ],
        'slaves' => [
            ['host' => '127.0.0.1', 'user' => 'root', 'password' => 'p455w0rd', 'port' => 3306],
        ],
    ],
];

$services->get('ConnectionPool')->setConfig($connections);
```

`namespace` pointe vers le driver à utiliser : `CCMBenchmark\Ting\Driver\Mysqli`, `CCMBenchmark\Ting\Driver\Pgsql`, ou `CCMBenchmark\Ting\Driver\SphinxQL` (qui parle le protocole MySQL).

Des options par base (comme le fuseau horaire) peuvent être fixées globalement :

```php
$services->get('ConnectionPool')->setDatabaseOptions([
    'world' => ['timezone' => 'UTC+1'],
]);
```

## Récupérer un repository et faire une première requête

```php
$cityRepository = $services->get('RepositoryFactory')->get('\sample\src\model\CityRepository');

// Par clé primaire
$city = $cityRepository->get(3);

// Requête libre
$query = $cityRepository->getQuery(
    'SELECT cit_id, cit_name FROM t_city_cit WHERE cou_code = :code LIMIT 3'
);
$collection = $query->setParams(['code' => 'FRA'])->query();

foreach ($collection as $result) {
    var_dump($result);
}
```

## Où voir du code exécutable

Le dépôt `ting` contient un dossier [`sample/`](../sample/) avec des scripts complets et fonctionnels :
- [`sample/src/main.php`](../sample/src/main.php) — bootstrap, requêtes, hydrateurs, transactions
- [`sample/src/main.postgresql.php`](../sample/src/main.postgresql.php) — équivalent PostgreSQL
- [`sample/src/mainUnitOfWork.php`](../sample/src/mainUnitOfWork.php) — insertions/mises à jour/suppressions via `UnitOfWork`
- [`sample/src/model/`](../sample/src/model/) — entités et repositories réels (`City`, `Country`, `CountryLanguage`, `Producer`, `Worker`, `Movie`, `Actor`)

Pour la suite de cette documentation, on continue avec l'exemple `City` / `CityRepository`.
