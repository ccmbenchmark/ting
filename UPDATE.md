# Update file
This file will track changes to public interfaces between 2 major versions.

## 4.0:
* PHP required version is now 8.1
* All classes are now fully typehinted with strict types
* PHPStan level raised to 7
* Rector configuration added for automated code quality improvements
* Serialization interfaces now have proper type hints:
    * ```SerializeInterface::serialize()``` now returns ```mixed``` explicitly
    * ```UnserializeInterface::unserialize()``` parameter is now typed ```mixed```
* Pgsql Driver now uses native PHP 8.1+ PgSql classes:
    * Internal ```$connection``` property type changed from ```resource``` to ```\PgSql\Connection```
    * Internal ```$result``` property type changed from ```resource``` to ```\PgSql\Result```
* ConnectionPoolInterface:
    * Added method ```setDatabaseOptions(array $options): void```
* DriverInterface:
    * Added method ```ping(): bool```
    * Added method ```setTimezone(string $timezone): static```
    * Removed deprecated method ```getInsertId()``` (use ```getInsertedId()``` instead)
* Mysqli Driver:
    * Removed deprecated method ```getInsertId()``` (use ```getInsertedId()``` instead)
* Pgsql Driver:
    * Removed deprecated method ```getInsertId()``` (use ```getInsertedId()``` instead)
    * Removed deprecated method ```getInsertIdForSequence()``` (use ```getInsertedIdForSequence()``` instead)
* QueryInterface:
    * Removed deprecated method ```getInsertId()``` (use ```getInsertedId()``` instead)
* Query class:
    * Removed deprecated method ```getInsertId()``` (use ```getInsertedId()``` instead)
* Generator class:
    * Removed method ```getByCriteriaWithOrderAndLimit()```
    * Method ```getByCriteria()``` now accepts ```$order``` and ```$limit``` parameters (merged functionality)
    * All methods now have strict return type hints
* Metadata class:
    * Removed method ```getGetter()```
    * Removed method ```getSetter()```
* UnitOfWork:
    * Removed deprecated method ```generateUid()```
    * Removed deprecated method ```generateUUID()```

## 3.0:
* PHP required version is now 5.5
* Cache data are incompatibles with previous major version, you should clean your cache data
* Cache interface is now provided by Doctrine/Cache, you need to rewrite all your code using CacheInterface
    or check if Doctrine/Cache provides an implementation for the cache datastore you need.
* Serializers Bool are renamed to Boolean for PHP7 compatibility
    So you should check your repository if you use CCMBenchmark\Ting\Driver\Pgsql\Serializer\Bool or
    CCMBenchmark\Ting\Driver\Mysqli\Serializer\Bool change Bool to Boolean
* Metadata was provided by Repository, they can now provided by any class, but you must now implement MetadataInitializer
* DriverInterface:
    * New ```setName``` method
* ResultInterface:
    * Removed method ```dataSeek```
    * Removed method ```format```
    * Removed Constructor
    * Added setter ```setConnectionName```
    * Added setter ```setDatabase```
    * Added setter ```setResult```
    * Added accessor ```getConnectionName```
    * Added accessor ```getDatabase```
    * Added method ```getNumRows```
* StatementInterface :
    * Added Constructor
* CollectionInterface:
    * ```set(\Iterator $result)``` changed to ```set(ResultInterface $result)```
    * Renamed method ```toArray``` to ```toCache```
    * Renamed method ```fromArray``` to ```fromCache```
    * Now implement ```IteratorAggregate``` and ```Countable```
    * Removed all methods ```rewind```, ```current```, ```key```, ```next```, ```valid```, ```count```, ```add```
* HydratorInterface:
    * Now implement ```IteratorAggregate```, ```Countable```
    * Old method ```hydrate``` is now ```setResult``` with a single argument ```ResultInterface```
* UnitOfWork
    * ```manage($entity)``` changed to ```manage(NotifyPropertyInterface $entity)```
    * ```isManaged($entity)``` changed to ```isManaged(NotifyPropertyInterface $entity)```
    * ```isNew($entity)``` changed to ```isNew(NotifyPropertyInterface $entity)```
    * ```pushSave($entity)``` changed to ```pushSave(NotifyPropertyInterface $entity)```
    * ```shouldBePersisted($entity)``` changed to ```shouldBePersisted(NotifyPropertyInterface $entity)```
    * ```propertyChanged($entity, $propertyName, $oldValue, $newValue)``` changed to ```propertyChanged(NotifyPropertyInterface $entity, $propertyName, $oldValue, $newValue)```
    * ```isPropertyChanged($entity, $propertyName)``` changed to ```isPropertyChanged(NotifyPropertyInterface $entity, $propertyName)```
    * ```detach($entity)``` changed to ```detach(NotifyPropertyInterface $entity)```
    * ```pushDelete($entity)``` changed to ```pushDelete(NotifyPropertyInterface $entity)```
    * ```shouldBeRemoved($entity)``` changed to ```shouldBeRemoved(NotifyPropertyInterface $entity)```
* PropertyListenerInterface
    * ```propertyChanged($entity, $propertyName, $oldValue, $newValue)``` changed to ```propertyChanged(NotifyPropertyInterface $entity, $propertyName, $oldValue, $newValue)```
