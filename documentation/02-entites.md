# Entités

Une entité Ting est un objet PHP « pur », sans héritage imposé par le framework.

## Pourquoi une entité a besoin de notifier ses changements

L'[`UnitOfWork`](07-unitofwork.md) doit savoir quelles propriétés ont changé sur un objet pour ne mettre à jour que les colonnes nécessaires (`UPDATE ... SET colonne_modifiee = ...`). Pour cela, l'entité doit implémenter le pattern Observer et notifier l'`UnitOfWork` à chaque changement.

Ting fournit tout ce qu'il faut :

- `CCMBenchmark\Ting\Entity\NotifyPropertyInterface` — interface à implémenter (une seule méthode : `addPropertyListener()`)
- `CCMBenchmark\Ting\Entity\NotifyProperty` — trait qui l'implémente déjà (gestion des listeners + notification)

```php
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
        return $this->name;
    }
}
```

Chaque fois qu'une propriété change, on appelle `propertyChanged($nomPropriete, $ancienneValeur, $nouvelleValeur)` **avant** d'affecter la nouvelle valeur. Le trait ignore l'appel si `$oldValue === $newValue` (pas de notification inutile).

## Ce que fait `UnitOfWork` avec cette notification

- Quand une entité est **hydratée depuis la base** (résultat d'une requête), l'[hydrateur](06-hydrateurs.md) l'enregistre automatiquement auprès de l'`UnitOfWork` (`manage()`) — elle devient « managée ».
- Une entité managée dont une propriété change se retrouve suivie par `propertyChanged()` (le trait notifie tous les listeners, dont l'`UnitOfWork`).
- Au moment de `process()`, l'`UnitOfWork` ne génère un `UPDATE` que sur les colonnes réellement modifiées pour les entités managées, ou un `INSERT` complet pour les entités neuves (jamais hydratées depuis la base).

Voir [UnitOfWork](07-unitofwork.md) pour le détail du cycle de persistance.

## Property hooks PHP 8.4+

Avec PHP 8.4, les property hooks permettent d'éviter les setters classiques tout en gardant la notification :

```php
class City implements NotifyPropertyInterface
{
    use NotifyProperty;

    public int $id { set(int $id) { $this->propertyChanged('id', $this->id ?? null, $id); $this->id = $id; } }
}
```

(Voir aussi le bundle Symfony `ting_bundle`, qui déclare les entités via des attributs PHP plutôt qu'un `initMetadata()` manuel — [Intégration Symfony](08-integration-symfony.md).)
