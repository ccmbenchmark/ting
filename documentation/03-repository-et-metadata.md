# Repository et Metadata

## Le Repository

Le `Repository` est le lien entre une entité et sa table en base, via les objets `Query`. Chaque entité a son propre repository, qui étend `CCMBenchmark\Ting\Repository\Repository` et implémente `MetadataInitializer`.

### Méthodes principales

| Méthode | Rôle |
|---|---|
| `getQuery(string $sql): Query` | requête libre, non préparée |
| `getPreparedQuery(string $sql): PreparedQuery` | requête préparée (statement réutilisable) |
| `getCachedQuery(string $sql)` / `getCachedPreparedQuery(string $sql)` | variantes mises en cache — voir [Cache](05-cache.md) |
| `getQueryBuilder(string $type): QueryInterface` | query builder `aura/sqlquery` (`Repository::QUERY_SELECT`\|`QUERY_INSERT`\|`QUERY_UPDATE`\|`QUERY_DELETE`) |
| `get($primariesKeyValue, $forceMaster = false)` | une entité par clé primaire (tableau si clé composite) |
| `getAll($forceMaster = false)` | toutes les lignes de la table |
| `getBy(array $criteria, $forceMaster = false, array $order = [], int $limit = 0)` | recherche par critères, avec tri et limite (depuis 3.9.0) |
| `getOneBy(array $criteria, $forceMaster = false)` | une seule entité par critères |
| `save($entity)` / `delete($entity)` | raccourcis de persistance immédiate (voir plus bas) |
| `startTransaction()` / `rollback()` / `commit()` | délèguent à la connexion **master** |
| `ping()` / `pingMaster()` | vérifie la connexion (si le driver le supporte) |
| `getCollection(?HydratorInterface $hydrator = null)` | collection vide à passer à `query()` avec un hydrateur particulier |
| `getMetadata()` | accès au `Metadata` du repository |

### `save()` et `delete()` : persistance immédiate

```php
$city = $cityRepository->get(3);
$city->setName('Nouveau nom');
$cityRepository->save($city);   // équivaut à : $unitOfWork->pushSave($city)->process();

$cityRepository->delete($city); // équivaut à : $unitOfWork->pushDelete($city)->process();
```

Ce sont de purs raccourcis vers l'[`UnitOfWork`](07-unitofwork.md) : `save()`/`delete()` empilent l'opération puis appellent immédiatement `process()`. Pratique pour « une entité, un aller-retour base » ; si vous devez persister plusieurs entités en un seul aller-retour (batch), passez directement par `$services->get('UnitOfWork')` (plusieurs `pushSave()`/`pushDelete()`, puis un seul `process()`).

## Metadata : déclarer le mapping

`initMetadata()` (statique, imposée par `MetadataInitializer`) retourne un `Metadata` décrivant la table et ses colonnes :

```php
use CCMBenchmark\Ting\Repository\Metadata;
use CCMBenchmark\Ting\Repository\MetadataInitializer;
use CCMBenchmark\Ting\Serializer\SerializerFactoryInterface;

class CityRepository extends \CCMBenchmark\Ting\Repository\Repository implements MetadataInitializer
{
    public static function initMetadata(SerializerFactoryInterface $serializerFactory, array $options = [])
    {
        $metadata = new Metadata($serializerFactory);

        $metadata->setEntity('sample\src\model\City');
        $metadata->setConnectionName('main');
        $metadata->setDatabase('world');
        $metadata->setTable('t_city_cit');
        // setSchema('public') pour un schéma Postgres non standard

        $metadata
            ->addField([
                'primary'       => true,
                'autoincrement' => true,
                'fieldName'     => 'id',
                'columnName'    => 'cit_id',
                'type'          => 'int',
            ])
            ->addField([
                'fieldName'  => 'name',
                'columnName' => 'cit_name',
                'type'       => 'string',
            ]);

        return $metadata;
    }
}
```

### Options de `addField()`

| Clé | Obligatoire | Rôle |
|---|---|---|
| `fieldName` | oui | nom de la propriété PHP de l'entité |
| `columnName` | oui | nom de la colonne SQL |
| `type` | oui | type de la valeur (voir ci-dessous) |
| `primary` | non | `true` pour une clé primaire (plusieurs champs primaires possibles) |
| `autoincrement` | non | combiné à `primary`, une seule colonne autoincrement supportée par table |
| `getter` / `setter` | non | nom de méthode explicite si la convention `get<FieldName>`/`set<FieldName>` ne convient pas |
| `serializer` | non | classe de serializer personnalisée (surcharge celle par défaut du `type`) |
| `serializer_options` | non | `['serialize' => [...], 'unserialize' => [...]]`, options passées au serializer |
| `sequenceName` | non | (PostgreSQL) nom de la séquence à utiliser pour récupérer l'autoincrement |

`addField()` lève une exception si `fieldName`, `columnName` ou `type` sont absents.

### Types disponibles

Les casts simples (`int`, `double`, `bool`, et par défaut `string`) sont gérés nativement, sans serializer. Les types suivants ont un serializer dédié, assigné automatiquement :

| `type` | Serializer |
|---|---|
| `datetime` | `CCMBenchmark\Ting\Serializer\DateTime` |
| `datetime_immutable` | `CCMBenchmark\Ting\Serializer\DateTimeImmutable` |
| `datetimezone` | `CCMBenchmark\Ting\Serializer\DateTimeZone` |
| `json` | `CCMBenchmark\Ting\Serializer\Json` |
| `ip` | `CCMBenchmark\Ting\Serializer\Ip` |
| `geometry` | `CCMBenchmark\Ting\Serializer\Geometry` (nécessite `brick/geo`) |
| `uuid` | `CCMBenchmark\Ting\Serializer\Uuid` (nécessite `symfony/uid`) |

Un type comme les enums PHP (`BackedEnum`) n'a pas de mapping automatique par `type` : il faut le déclarer explicitement via `serializer` + `serializer_options` :

```php
$metadata->addField([
    'fieldName'          => 'status',
    'columnName'         => 'status',
    'type'               => 'string',
    'serializer'         => \CCMBenchmark\Ting\Serializer\BackedEnum::class,
    'serializer_options' => ['unserialize' => ['enum' => StatusEnum::class]],
]);
```

### Écrire son propre serializer

Un serializer implémente `SerializeInterface` et/ou `UnserializeInterface` (ou `SerializerInterface` pour les deux) :

```php
$metadata->addField([
    'fieldName'  => 'tags',
    'columnName' => 'tags_name',
    'type'       => 'json',
    'serializer' => \Bouh\Awesome\Serializer::class,
]);
```

## QueryBuilder

Pour chaîner des requêtes sans écrire de SQL brut, Ting fournit un QueryBuilder via [`aura/sqlquery`](https://github.com/auraphp/Aura.SqlQuery) :

```php
$repository->getQueryBuilder(Repository::QUERY_SELECT);
$repository->getQueryBuilder(Repository::QUERY_INSERT);
$repository->getQueryBuilder(Repository::QUERY_UPDATE);
$repository->getQueryBuilder(Repository::QUERY_DELETE);
```

Le dialecte utilisé (`mysql` ou `pgsql`) est déduit automatiquement du driver configuré sur la connexion du repository.
