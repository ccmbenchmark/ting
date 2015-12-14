# Update file
This file will track changes to public interfaces between 2 major versions.

## 3.0:
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
* HydratorInterface:
    * New mandatory parameters for ```hydrate``` method
    * New method ```isAggregator```
