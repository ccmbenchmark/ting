# Intégration Symfony (`ting_bundle`)

Ting s'intègre à Symfony (4.4 à 7.x) via un bundle dédié, maintenu dans un dépôt séparé : [`ccmbenchmark/ting_bundle`](https://gitlab.ccmbg.com/core/ting_bundle). C'est ce bundle qui remplace l'ancienne section « Getting started with Symfony 2.8 » de la doc historique — l'intégration a été entièrement revue depuis.

## Installation

```bash
composer require ccmbenchmark/ting_bundle
```

## Configuration principale

Les connexions et bases se configurent en YAML (équivalent Symfony du tableau PHP `ConnectionPool` vu dans le [démarrage rapide](01-demarrage-rapide.md)) :

```yaml
ting:
    connections:
        main:
            namespace: CCMBenchmark\Ting\Driver\Mysqli
            master:
                host:     localhost
                user:     world_sample
                password: world_sample
                port:     3306
            slaves:
                slave1: { host: 127.0.0.1, user: world_sample_ro, password: world_sample_ro, port: 3306 }
                slave2: { host: 127.0.1.1, user: world_sample_ro, password: world_sample_ro, port: 3306 }

    databases_options:
        baz:
            timezone: 'Europe/Paris'
```

## Déclarer les repositories et entités

Deux approches possibles :

### 1. Scan par YAML (sans attributs)

```yaml
ting:
    repositories:
        Acme:
            namespace: Acme\DemoBundle\Entity
            directory: "@DemoBundle/Entity"
            options:
                Acme\DemoBundle\BazRepository:
                    extra:
                        bar: hello
                default:
                    connection: main
                    database: baz
```

### 2. Attributs PHP (approche recommandée)

Plutôt que d'écrire `initMetadata()` à la main (voir [Repository et Metadata](03-repository-et-metadata.md)), le bundle permet de déclarer le mapping directement sur l'entité avec des attributs :

```php
use CCMBenchmark\TingBundle\Schema;
use CCMBenchmark\Ting\Entity\NotifyProperty;
use CCMBenchmark\Ting\Entity\NotifyPropertyInterface;

#[Schema\Table('city_table', 'main', '%env(DATABASE_NAME)%', CityRepository::class)]
class City implements NotifyPropertyInterface
{
    use NotifyProperty;

    #[Schema\Column(autoIncrement: true, primary: true)]
    public int $id { set(int $id) { $this->propertyChanged('id', $this->id ?? null, $id); $this->id = $id; } }

    #[Schema\Column(column: 'field')]
    public string $fieldWithSpecifiedColumnName { set(string $v) { /* ... */ $this->fieldWithSpecifiedColumnName = $v; } }
}
```

(Exemple utilisant les property hooks PHP 8.4 — voir aussi [Entités](02-entites.md#property-hooks-php-84).)

## Autres fonctionnalités du bundle

- **User Provider Security** — utiliser un repository Ting comme fournisseur d'utilisateurs Symfony (`providers.app_user_provider.ting.class` / `property`).
- **Contrainte d'unicité** (`UniqueEntity`) sur une colonne/table.
- **Value Resolver** — injection automatique d'une entité dans un contrôleur à partir d'un paramètre de route (`{userId:user}` ou `#[MapEntity(expr: '...')]`).
- **Intégration au Profiler Symfony** — deux DataCollectors auto-enregistrés (`ting.driver`, `ting.cache`) pour inspecter les requêtes exécutées et les hits de cache dans la barre de debug.
- **Cache warmer / clearer** des métadonnées — branché sur `cache:warmup` / `cache:clear`.
- **Résolution dynamique de configuration** — permet de calculer certaines options de connexion au runtime.
- **Bridge vers le Serializer Symfony** — pont optionnel entre les serializers Ting et le composant `symfony/serializer`.

Pour le détail exact de chaque fonctionnalité, se référer au [README.md du dépôt `ting_bundle`](https://gitlab.ccmbg.com/core/ting_bundle), qui reste la référence à jour (cette page en donne une vue d'ensemble organisée, pas une copie exhaustive).
