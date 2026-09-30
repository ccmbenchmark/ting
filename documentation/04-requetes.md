# Requêtes

## Requête de lecture

Une requête s'obtient via le `Repository` :

```php
$query = $cityRepository->getQuery(
    'SELECT id, name FROM user WHERE name = :name'
);
$query->setParams(['name' => 'Sylvain']);
$collection = $query->query();

foreach ($collection as $data) {
    print_r($data);
}
```

- `setParams(array $params)` définit les paramètres nommés (`self`, chaînable).
- `query(?CollectionInterface $collection = null)` exécute la requête et retourne une `Collection` (`Iterator` + `Countable` — `count($collection)` fonctionne comme sur un tableau). Le format exact des lignes retournées dépend de l'[hydrateur](06-hydrateurs.md) utilisé.
- Par défaut, une requête de lecture part sur un des **slaves** configurés (round-robin). Pour forcer le **master** :

```php
$query->selectMaster(true);
```

- En cas d'échec de la requête, une exception `CCMBenchmark\Ting\Driver\QueryException` est levée.

## Requête d'écriture

Même logique, mais on appelle `execute()` plutôt que `query()` — l'exécution se fait toujours sur le master :

```php
$query = $cityRepository->getQuery('INSERT INTO t_city_cit (cit_name) VALUES (:name)');
$query->setParams(['name' => 'Palaiseau'])->execute();

$id = $query->getInsertedId();     // identifiant auto-incrémenté
$affected = $query->getAffectedRows();
```

> `getInsertId()` existe encore mais est **dépréciée depuis la 3.8** au profit de `getInsertedId()` (renommage pour cohérence avec `getAffectedRows()`) — préférez systématiquement `getInsertedId()` dans du code neuf.

## Requêtes préparées

Pour un statement réutilisable (même requête, paramètres différents, exécutée plusieurs fois) :

```php
$preparedQuery = $cityRepository->getPreparedQuery(
    'SELECT c.* FROM t_city_cit c INNER JOIN t_country_cou co ON (c.cou_code = co.cou_code) WHERE co.cou_code = :code LIMIT 2'
);
$preparedQuery->prepareQuery();

$collectionFR = $preparedQuery->setParams(['code' => 'FRA'])->query();
$collectionBE = $preparedQuery->setParams(['code' => 'BEL'])->query();
```

## Transactions

Le `Repository` relaie directement vers la connexion master (pas de gestion de savepoint ou de transaction imbriquée) :

```php
$cityRepository->startTransaction();
try {
    $cityRepository->getQuery('INSERT INTO t_city_cit (cit_name) VALUES (:name)')
        ->setParams(['name' => 'Test'])
        ->execute();
    $cityRepository->commit();
} catch (\Exception $e) {
    $cityRepository->rollback();
}
```

## Requêtes mises en cache

Voir [Cache](05-cache.md) pour `getCachedQuery()` / `getCachedPreparedQuery()`.

## QueryBuilder

Voir la section correspondante dans [Repository et Metadata](03-repository-et-metadata.md#querybuilder).
