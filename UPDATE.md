# Update file
This file will track changes to public interfaces between 2 major versions.

## 3.0:
* PHP required version is now 5.5
* Cache data are incompatibles with previous major version, you should clean your cache data
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
    * Changed type hinting : method ```set``` now type hints the new ```ResultInterface``` (was ```\Iterator```)
    * Renamed method ```toArray``` to ```toCache```
    * Renamed method ```fromArray``` to ```fromCache```
    * Now implement ```IteratorAggregate``` and ```Countable```
    * Removed all methods ```rewind```, ```current```, ```key```, ```next```, ```valid```, ```count```, ```add```
* HydratorInterface:
    * Now implement ```IteratorAggregate```, ```Countable```
    * Old method ```hydrate``` is now ```setResult``` with a single argument ```ResultInterface```
