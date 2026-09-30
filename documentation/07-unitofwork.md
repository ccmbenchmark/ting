# UnitOfWork

Le rôle de l'`UnitOfWork` (`CCMBenchmark\Ting\UnitOfWork`) est de suivre les changements d'une entité pour les persister en base — insertion, mise à jour ou suppression.

## Principe

On empile les opérations à faire, puis on demande à l'`UnitOfWork` de tout traiter en une fois :

```php
$unitOfWork = $services->get('UnitOfWork');

$unitOfWork->pushSave($city1);
$unitOfWork->pushSave($city2);
$unitOfWork->pushDelete($city3);
$unitOfWork->process();
```

`process()` parcourt les entités en attente et, selon leur état :
- génère un `UPDATE` **uniquement sur les propriétés effectivement modifiées** pour une entité déjà managée (déjà hydratée depuis la base) ;
- génère un `INSERT` complet pour une entité neuve, puis positionne la valeur autoincrement renvoyée par la base et l'enregistre comme managée ;
- génère un `DELETE` puis détache l'entité pour une suppression.

## `pushSave` pour insérer ou mettre à jour

`pushSave()` sert aussi bien à insérer qu'à mettre à jour une entité — c'est l'état de l'entité (managée ou neuve) qui détermine si un `INSERT` ou un `UPDATE` sera généré :

```php
$city = $cityRepository->get(3);   // hydratée depuis la base -> managée
$city->setName('Nouveau nom');
$unitOfWork->pushSave($city);      // -> UPDATE sur la colonne name uniquement

$city2 = new City();               // jamais hydratée -> neuve
$city2->setName('Bouh');
$unitOfWork->pushSave($city2);     // -> INSERT

$unitOfWork->process();
```

Après un `INSERT` réussi, si l'entité a une propriété autoincrement (configurée dans le [`Metadata`](03-repository-et-metadata.md)), sa valeur est renseignée automatiquement sur l'entité.

## Détacher des entités (traitement par lots)

En traitant un grand nombre d'objets, il faut détacher ceux qu'on n'utilise plus pour libérer la mémoire :

```php
$unitOfWork->detach($entity);
$unitOfWork->detachAll(); // détache tout d'un coup
```

L'`UnitOfWork` utilise en interne des `WeakMap` — les entités détachées (ou qui ne sont plus référencées ailleurs) ne provoquent pas de fuite mémoire.

## Autres méthodes utiles

| Méthode | Rôle |
|---|---|
| `isManaged($entity): bool` | l'entité est-elle suivie (déjà hydratée ou déjà persistée) ? |
| `isNew($entity): bool` | l'entité est-elle en attente de persistance à l'état neuf ? |
| `shouldBePersisted($entity): bool` | l'entité est-elle en attente d'un save (peu importe l'état) ? |
| `isPropertyChanged($entity, $propertyName): bool` | cette propriété a-t-elle changé depuis le dernier `process()` ? |
| `shouldBeRemoved($entity): bool` | l'entité est-elle en attente de suppression ? |

## `Repository::save()` / `delete()`

Pour le cas simple « une entité, un aller-retour base immédiat », le [`Repository`](03-repository-et-metadata.md) propose des raccourcis :

```php
$cityRepository->save($city);   // = $unitOfWork->pushSave($city)->process();
$cityRepository->delete($city); // = $unitOfWork->pushDelete($city)->process();
```

Ces méthodes n'ajoutent aucune logique propre — elles délèguent intégralement à l'`UnitOfWork`. Pour grouper plusieurs écritures en un seul aller-retour (batch), il faut passer directement par `$services->get('UnitOfWork')` comme montré plus haut, plutôt que d'appeler `save()`/`delete()` en boucle (qui ferait un `process()` par entité).

## Exemple complet

Voir [`sample/src/mainUnitOfWork.php`](../sample/src/mainUnitOfWork.php) pour un script exécutable de bout en bout (récupération, modification, insertion, suppression).
