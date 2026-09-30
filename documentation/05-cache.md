# Cache

Ting propose deux niveaux de cache : le cache **d'une requête** (résultat mis en cache) et un composant de cache **générique** utilisable indépendamment.

## Requêtes mises en cache

Le résultat d'une requête faite avec les objets « cached » sera récupéré du cache si l'entrée existe déjà, ou exécuté puis inséré dans le cache sinon. Le résultat mis en cache ne dépend pas de l'hydrateur utilisé.

### Requête simple

```php
$query = $cityRepository->getCachedQuery(
    'SELECT cit_id, cit_name FROM t_city_cit WHERE cou_code = :code LIMIT 1'
);
$query->setTtl(300);            // durée de vie en secondes (5 min)
$query->setCacheKey('cityFRA');  // clé de cache

$collection = $query->setParams(['code' => 'FRA'])->query();

var_dump($collection->isFromCache()); // true si le résultat vient du cache
```

`setTtl()` et `setCacheKey()` sont **obligatoires** : sans eux, une `QueryException` est levée à l'exécution.

Autres méthodes utiles sur `Cached\Query` :
- `setVersion($version)` — inclut une version dans la clé, pratique pour invalider en masse en changeant juste la version.
- `setForce(true)` — bypasse le cache et force la requête (utile pour rafraîchir manuellement une entrée).

### Requête préparée

Même principe, avec `getCachedPreparedQuery()` :

```php
$query = $cityRepository->getCachedPreparedQuery('SELECT ... WHERE cou_code = :code');
$query->setTtl(300);
$query->setCacheKey('listOfUser');
```

## Le composant de cache générique

Le composant de cache générique (`CCMBenchmark\Ting\Cache\Cache`) reste basé sur [`doctrine/cache`](https://github.com/doctrine/cache) : `CacheInterface` étend `\Doctrine\Common\Cache\Cache` et n'ajoute qu'un `setLogger()`.

### Configuration

```php
use CCMBenchmark\Ting\Cache\Cache;

$cache = new Cache();
$cache->setCache(new \Doctrine\Common\Cache\ArrayCache()); // ou FilesystemCache, un adaptateur symfony/cache, etc.
```

### Sauvegarder / lire / effacer une valeur

```php
$cache->save('ma_cle', $valeur, 300); // 300 secondes de durée de vie
$cache->fetch('ma_cle');
$cache->contains('ma_cle');
$cache->delete('ma_cle');
```

### Durée de vie infinie

```php
$cache->save('ma_cle', $valeur, 0); // 0 = infini (convention Doctrine Cache)
```

> Il n'existe pas d'API « plusieurs clés à la fois » (`saveMultiple`/`fetchMultiple`) sur `CacheInterface` — chaque clé est traitée individuellement, une par une.
