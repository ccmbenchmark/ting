# Update file
This file will track changes to public interfaces between 2 major versions.

## 3.0:
* PHP required version is now 5.5
* Cache data are incompatibles with previous major version, you should clean your cache data
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
