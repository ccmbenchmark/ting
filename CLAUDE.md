# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Projet Ting

Ting est le DataMapper PHP maison de CCMBenchmark (alternative à Doctrine), sans PDO ni XML, avec support MySQL et PostgreSQL.

**Dépôt** : https://gitlab.ccmbg.com/core/ting (rapatrié depuis GitHub, historique conservé — voir COREPHP-649)

**Stack technique :**
- PHP >=8.0
- `aura/sqlquery` pour le QueryBuilder
- `symfony/property-access`
- Tests unitaires : [atoum](https://atoum.org/)

## 🏗️ Architecture

```
src/Ting/
├── Repository/     # Pattern Repository (point d'entrée pour les requêtes)
├── Query/          # Construction et exécution des requêtes
├── Entity/         # Interfaces/traits pour les entités (NotifyProperty, etc.)
├── Driver/         # Drivers bas niveau (Mysqli, Pgsql)
├── Serializer/     # Sérialisation des types (Enum, DateTimeImmutable, Uuid...)
├── Exceptions/     # Exceptions dédiées
├── Logger/         # Logging des requêtes
└── Util/           # Utilitaires
```

Le bundle Symfony associé (`ccmbenchmark/ting_bundle`) vit dans un dépôt séparé : https://gitlab.ccmbg.com/core/ting_bundle

## 📚 Documentation

Le dossier [`documentation/`](documentation/README.md) contient une doc pédagogique par sujet (réécrite en 2026, voir COREPHP-744) — à consulter en priorité selon le sujet plutôt que de tout lire :

- [01-demarrage-rapide.md](documentation/01-demarrage-rapide.md) — installation, bootstrap `Services`/`ConnectionPool`
- [02-entites.md](documentation/02-entites.md) — `NotifyProperty`, notification des changements
- [03-repository-et-metadata.md](documentation/03-repository-et-metadata.md) — mapping table ↔ entité, méthodes du Repository
- [04-requetes.md](documentation/04-requetes.md) — `Query`, requêtes préparées, transactions, QueryBuilder
- [05-cache.md](documentation/05-cache.md) — requêtes mises en cache, composant de cache générique
- [06-hydrateurs.md](documentation/06-hydrateurs.md) — `Hydrator`, `HydratorArray`, `HydratorAggregator`, `HydratorRelational`
- [07-unitofwork.md](documentation/07-unitofwork.md) — persister (insert/update/delete)
- [08-integration-symfony.md](documentation/08-integration-symfony.md) — le bundle `ting_bundle`

## 🎯 Concepts clés

Le [README.md](README.md) et [`documentation/`](documentation/README.md) sont la référence détaillée (bootstrap, exemples). Résumé :

- **Services** : point d'entrée (`new CCMBenchmark\Ting\Services()`), conteneur Pimple qui expose `ConnectionPool`, `MetadataRepository`, `UnitOfWork`, `RepositoryFactory`, les Hydrators, etc.
- **Metadata / MetadataInitializer** : le mapping table ↔ entité se déclare dans le Repository via `initMetadata()` qui retourne un `Metadata` (`setEntity`, `setConnectionName`, `setDatabase`, `setTable`, `addField(...)`). C'est le mécanisme central de mapping — pas d'attributs PHP côté `ting` (ça, c'est `ting_bundle`).
- **Repository** : chaque entité a un repository dédié (`extends CCMBenchmark\Ting\Repository\Repository`, implements `MetadataInitializer`) qui expose `get()`, `getQuery()`, `getPreparedQuery()`, `getQueryBuilder()`, `startTransaction()`/`rollback()`.
- **NotifyProperty** : les entités doivent utiliser `CCMBenchmark\Ting\Entity\NotifyProperty` + `NotifyPropertyInterface` et appeler `propertyChanged()` dans chaque setter, pour que `UnitOfWork` puisse détecter les changements.
- **UnitOfWork** : API d'écriture (`pushSave()`, `pushDelete()`, `process()` pour exécuter les insert/update/delete en attente ; `isManaged()`, `isNew()`, `detach()`/`detachAll()`).
- **Hydrators** : `Hydrator` (défaut), `HydratorSingleObject` (agrégation simple + `mapAliasTo`/`mapObjectTo`), `HydratorAggregator` (groupement de lignes), `HydratorRelational` (relations déclaratives via `RelationOne`/`RelationMany`), `HydratorArray`.
- **Serializers** : `DateTime(Immutable)`, `DateTimeZone`, `BackedEnum`, `Uuid`, `Ip`, `Json`, `Geometry`.
- **Drivers** : `Mysqli`, `Pgsql`, `SphinxQL` — sélectionnés via la clé `namespace` de la config de connexion.
- **QueryBuilder** : fourni par [aura/sqlquery](https://github.com/auraphp/Aura.SqlQuery), pas d'abstraction maison au-delà.

## 🧪 Tests

Tests unitaires avec [atoum](http://docs.atoum.org/), configuration dans `.atoum.php`.

```bash
# Lancer la suite de tests
vendor/bin/atoum -c .bootstrap.atoum.php --configurations .atoum.php

# Rapport de couverture généré dans tests/coverage/
```

Les tests vivent dans `tests/units/`, les fixtures dans `tests/fixtures/`.

## ⚙️ Composer

```bash
composer install
composer dump-autoload
```

## 📝 Changelog

Le fichier `CHANGELOG` (à la racine, sans extension) doit être mis à jour à chaque release, format `X.Y.Z (date):` suivi des changements.

## 🎓 Bonnes pratiques

- **Backward Compatibility** : Ting suit [semver](http://semver.org/). Un BC break nécessite une discussion préalable (voir `CONTRIBUTING.md`) et un bump de version majeure.
- **Code style** : PSR-2/PSR-12.
- **Merge Request** : respecter le template décrit dans `CONTRIBUTING.md` (Bug fix?, New feature?, BC breaks?, Deprecations?, Tests pass?, Fixed tickets).

## Git

- Dépôt rapatrié de GitHub vers GitLab (COREPHP-649), historique conservé. GitLab fait foi : les versions naissent ici.
- `master` et les tags sont recopiés sur https://github.com/ccmbenchmark/ting (Packagist lit ce dépôt), en avance rapide seulement, jamais par push --force.
- Ne pas recréer de documentation en ligne externe (tech.ccmbg.com) : ce README/CLAUDE.md et le dossier `documentation/` font foi.
