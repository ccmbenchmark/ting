# Hydrateurs

L'hydrateur transforme le résultat brut d'une requête SQL en objets (ou tableaux), selon le mapping déclaré dans le [`Metadata`](03-repository-et-metadata.md) de chaque repository.

## L'hydrateur par défaut (`Hydrator`)

Sans rien préciser, `query()` utilise l'hydrateur par défaut : chaque ligne devient un tableau associatif `[alias_table => entité]`.

```php
$query = $cityRepository->getQuery(
    "SELECT id, name, c.text FROM user LEFT JOIN comment c ON (c.user_id = user.id) WHERE name = :name"
);
$collection = $query->setParams(['name' => 'Sylvain'])->query();
```

```php
$row = [
    'user' => User(id: 3, name: "Sylvain"),
    'c'    => Comment(text: "Bonjour tout le monde"),
];
```

Si la jointure ne retourne aucune donnée, la clé correspondante (`c` ci-dessus) vaut `null`.

Chaque entité `NotifyPropertyInterface` hydratée est automatiquement enregistrée auprès de l'[`UnitOfWork`](07-unitofwork.md) (elle devient « managée ») — c'est ce qui permet ensuite à `save()` de générer un `UPDATE` plutôt qu'un `INSERT`.

### Colonnes non mappées (sans metadata)

Une colonne agrégée (`SUM(price)`) ou une colonne non déclarée dans le `Metadata` devient un `stdClass`, accessible à la clé `0` du tableau retourné :

```php
$row = [
    0         => (object) ['total' => 43, 'my_extra_column' => 'Bic'],
    'article' => Article(name: "Stylo"),
];
```

### Mapper une colonne non mappée vers un objet (`mapAliasTo`)

```php
use CCMBenchmark\Ting\Repository\Hydrator;

$hydrator = new Hydrator();
$hydrator->mapAliasTo('nb_books', 'user', 'setNbBooks');

$collection = $query->query($cityRepository->getCollection($hydrator));
// $row['user'] est un User dont setNbBooks() a été appelé avec la valeur de la colonne nb_books
```

### Composition d'objets (`mapObjectTo`)

Pour injecter un objet issu d'une jointure dans un autre (ex. `Country` dans `City`) :

```php
$hydrator = new Hydrator();
$hydrator->mapObjectTo('co', 'cit', 'setCountry');
// $row['cit'] est une City dont setCountry() a reçu l'objet Country ('co')
```

### Désérialiser une colonne sans metadata

```php
$hydrator = new Hydrator();
$hydrator->unserializeAliasWith('fetchedDate', $services->get('SerializerFactory')->get(\CCMBenchmark\Ting\Serializer\DateTime::class));
```

## L'hydrateur pour un seul objet (`HydratorSingleObject`)

L'hydrateur par défaut est pensé pour plusieurs objets par ligne (jointures). Quand une requête est censée retourner un seul type d'objet par ligne, `HydratorSingleObject` est plus direct — c'est d'ailleurs celui utilisé en interne par `Repository::getAll()`, `getBy()` et `getOneBy()` :

```php
use CCMBenchmark\Ting\Repository\HydratorSingleObject;

$collection = $cityRepository
    ->getQuery('SELECT id, name FROM user WHERE name = :name')
    ->setParams(['name' => 'Sylvain'])
    ->query($cityRepository->getCollection(new HydratorSingleObject()));
// $collection est une collection d'objets User directement (pas de tableau ['user' => ...])
```

## L'hydrateur tableau (`HydratorArray`)

Pour des besoins de rapport/export où l'on ne veut pas d'entités métier (juste les colonnes brutes) : chaque ligne devient un simple tableau associatif `colonne => valeur`, sans passage par l'`UnitOfWork`.

```php
use CCMBenchmark\Ting\Repository\HydratorArray;

$collection = $query->query($cityRepository->getCollection(new HydratorArray()));
```

## L'hydrateur d'agrégation (`HydratorAggregator`)

Permet d'agréger un ensemble de résultats — par exemple, une collection d'utilisateurs, chacun avec sa liste de livres issue d'une jointure.

```php
use CCMBenchmark\Ting\Repository\HydratorAggregator;

$query = $cityRepository->getQuery(
    'SELECT user.id, user.name, book.id, book.name
     FROM user
     INNER JOIN book ON (book.user_id = user.id)
     ORDER BY user.id'
);

$hydrator = new HydratorAggregator();
$hydrator->callableIdIs(fn ($result) => $result['user']->getId());
$hydrator->callableDataIs(fn ($result) => $result['book']);
$hydrator->callableFinalizeAggregate(function ($result, $books) {
    $result['user']->setBooks($books);
    return $result['user'];
});

$collection = $query->query($cityRepository->getCollection($hydrator));
```

- `callableIdIs` retourne la clé d'agrégation (l'identifiant de l'utilisateur ici). **Important : la requête SQL doit être triée sur cette clé** (`ORDER BY user.id`), sinon le résultat sera partiel.
- `callableDataIs` retourne la donnée à agréger (un livre ici).
- `callableFinalizeAggregate` est optionnel : si on l'omet, le résultat agrégé est placé dans la clé `aggregate` de la collection plutôt que d'être fusionné dans l'objet parent.

## L'hydrateur relationnel (`HydratorRelational`)

Version déclarative de l'agrégation, qui gère plusieurs niveaux de profondeur (ex. utilisateurs → livres → auteurs) :

```php
use CCMBenchmark\Ting\Repository\Hydrator;
use CCMBenchmark\Ting\Repository\HydratorRelational;

$query = $cityRepository->getQuery(
    'SELECT user.id, user.name, book.id, book.name, author.id, author.name
     FROM user
     INNER JOIN book ON (book.user_id = user.id)
     INNER JOIN author ON (author.id = book.author_id)
     ORDER BY user.id'
);

$hydrator = new HydratorRelational();
$hydrator->addRelation(new Hydrator\RelationMany(
    new Hydrator\AggregateFrom('book'),
    new Hydrator\AggregateTo('user'),
    'setBooks'
));
$hydrator->addRelation(new Hydrator\RelationOne(
    new Hydrator\AggregateFrom('author'),
    new Hydrator\AggregateTo('book'),
    'setAuthor'
));
$hydrator->callableFinalizeAggregate(fn ($row) => $row['user']);

$collection = $query->query($cityRepository->getCollection($hydrator));
```

- `RelationMany` : relation 1-n (le setter reçoit un tableau d'entités liées).
- `RelationOne` : relation 1-1 (le setter reçoit directement l'entité liée).
- `AggregateFrom` / `AggregateTo` désignent respectivement la table source et la table cible de la relation (alias SQL).
- Comme pour `HydratorAggregator`, `callableFinalizeAggregate` est optionnel : sans lui, le résultat garde sa forme `['user' => [...]]` plutôt que d'être aplati.

> Cet hydrateur charge et hydrate toutes les lignes d'un coup — on perd la lecture paresseuse (lazy) qu'offre l'hydrateur par défaut.
>
> L'identity map (déduplication des objets déjà hydratés) est forcée à `true` pour cet hydrateur : toute tentative de la désactiver lève une exception.

## Tableau récapitulatif

| Hydrateur | Usage |
|---|---|
| `Hydrator` (défaut) | cas général, jointures simples, `[table => entité]` |
| `HydratorSingleObject` | un seul type d'objet par ligne (utilisé par `getAll`/`getBy`/`getOneBy`) |
| `HydratorArray` | tableaux bruts `colonne => valeur`, pas d'entités ni d'UnitOfWork |
| `HydratorAggregator` | regroupement de lignes via callables (parent + liste d'enfants) |
| `HydratorRelational` | relations déclaratives à N niveaux de profondeur |
