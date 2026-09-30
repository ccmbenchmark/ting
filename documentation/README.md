# Documentation Ting

Ting est le datamapper PHP léger de CCM Benchmark : pas de magie, pas de PDO, pas de XML — des requêtes SQL et des objets. Il supporte MySQL (`Mysqli`), PostgreSQL (`Pgsql`) et SphinxQL.

Cette documentation reconstruit et met à jour l'ancienne doc Sphinx qui était publiée sur `tech.ccmbg.com/ting/doc/` (abandonnée depuis le rapatriement du dépôt vers GitLab, voir [COREPHP-649](https://gitlab.ccmbg.com/core/ting) et [COREPHP-744](https://gitlab.ccmbg.com/core/ting)).

> Pour une référence encore plus à jour au fil des versions, le [README.md](../README.md) et le [CHANGELOG](../CHANGELOG) du dépôt font foi. Cette documentation vise à être plus pédagogique / organisée par sujet.

## Sommaire

1. [Démarrage rapide](01-demarrage-rapide.md) — installation, bootstrap, premier repository, premières requêtes
2. [Entités](02-entites.md) — `NotifyProperty`, notification des changements
3. [Repository et Metadata](03-repository-et-metadata.md) — mapping table ↔ entité, méthodes du Repository
4. [Requêtes](04-requetes.md) — `Query`, requêtes préparées, QueryBuilder
5. [Cache](05-cache.md) — requêtes mises en cache, composant de cache générique
6. [Hydrateurs](06-hydrateurs.md) — comment les lignes SQL deviennent des objets
7. [UnitOfWork](07-unitofwork.md) — persister (insert/update/delete)
8. [Intégration Symfony](08-integration-symfony.md) — le bundle `ting_bundle`

## Versions concernées

Cette documentation décrit l'API de Ting telle qu'elle existe dans la branche `master` du dépôt (dernière version stable au moment de l'écriture : `3.12.1`, une `4.0.0-rc.1` est en préparation). Les fonctionnalités marquées **Depuis X.Y** ou **Dépréciée depuis X.Y** sont signalées quand c'est pertinent.
