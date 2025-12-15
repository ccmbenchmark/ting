UPGRADE FROM 3.X to 4.0
=======================

PHP Version
-----------

* PHP 8.0 support has been dropped. The minimum required version is now **PHP 8.1**.
* Update your `composer.json` to require `"php": ">=8.1"`

Generator
---------

* The `Generator::getByCriteriaWithOrderAndLimit()` method has been removed.
* Use `Generator::getByCriteria()` instead, which now accepts optional `$order` and `$limit` parameters:

```php
// Before (3.x):
$generator->getByCriteriaWithOrderAndLimit(['status' => 'active'], ['name' => 'ASC'], 10);

// After (4.0):
$generator->getByCriteria(['status' => 'active'], ['name' => 'ASC'], 10);
```

Metadata
--------

* The `Metadata::getGetter()` method has been removed.
* The `Metadata::getSetter()` method has been removed.
* If you were using these methods, you should define custom getters/setters directly in your field configuration:

```php
// In your Repository::initMetadata():
$metadata->addField([
    'fieldName' => 'myField',
    'columnName' => 'my_field',
    'type' => 'string',
    'getter' => 'getMyCustomField',  // Custom getter method name
    'setter' => 'setMyCustomField',  // Custom setter method name
]);
```

UnitOfWork
----------

* The `UnitOfWork::generateUid()` method has been removed.
* The `UnitOfWork::generateUUID()` method has been removed (was deprecated in 3.x).
* If you need unique identifiers, use PHP's built-in functions like `uniqid()` or `spl_object_hash()` directly.

Query and Driver Methods
-------------------------

### Removed getInsertId() Methods

All deprecated `getInsertId()` methods have been removed. Use `getInsertedId()` instead:

**QueryInterface and Query class:**
```php
// Before (3.x):
$query->getInsertId();

// After (4.0):
$query->getInsertedId();
```

**DriverInterface, Mysqli\Driver, and Pgsql\Driver:**
```php
// Before (3.x):
$driver->getInsertId();

// After (4.0):
$driver->getInsertedId();
```

**PostgreSQL sequences:**
```php
// Before (3.x):
$pgsqlDriver->getInsertIdForSequence('my_sequence');

// After (4.0):
$pgsqlDriver->getInsertedIdForSequence('my_sequence');
```

Serialization Interfaces
------------------------

If you have custom serializers, update the type hints:

**SerializeInterface:**
```php
// Before (3.x):
public function serialize($toSerialize, array $options = []): string

// After (4.0):
public function serialize($toSerialize, array $options = []): mixed
```

**UnserializeInterface:**
```php
// Before (3.x):
public function unserialize($serialized, array $options = [])

// After (4.0):
public function unserialize(mixed $serialized, array $options = []): mixed
```

Note: The `serialize()` method now returns `mixed` instead of `string` to support more flexible serialization formats.

PostgreSQL Driver
-----------------

* The PostgreSQL driver now uses native PHP 8.1+ `PgSql\Connection` and `PgSql\Result` classes instead of resources.
* This is an **internal change** and should not affect most user code.
* If you were using reflection or type checking on internal driver properties, update your code:

```php
// Before (3.x):
// $connection was a resource

// After (4.0):
// $connection is a \PgSql\Connection instance
```

DriverInterface - New Required Methods
---------------------------------------

If you have implemented custom drivers, you must implement these new methods:

```php
interface DriverInterface
{
    // New methods in 4.0:
    public function ping(): bool;
    public function setTimezone(?string $timezone = null): void;
}
```

**Implementation example:**
```php
public function ping(): bool
{
    // Check if connection is alive
    // Return true if connected, false otherwise
}

public function setTimezone(?string $timezone = null): void
{
    // Set the database connection timezone
    // e.g., for MySQL: SET time_zone = '+00:00'
}
```

ConnectionPoolInterface - New Method
-------------------------------------

If you have implemented custom connection pools, you must implement:

```php
interface ConnectionPoolInterface
{
    // New method in 4.0:
    public function setDatabaseOptions(array $options): void;
}
```

Type Hints and Strict Types
----------------------------

* All methods have full type hints for parameters and return types.
* If you extend Ting classes or implement Ting interfaces, ensure your signatures match exactly.
* Pay special attention to:
  - Return type declarations (`: void`, `: static`, `: mixed`, etc.)
  - Parameter types (`string`, `array`, `?int`, etc.)
  - Nullable types where applicable

Example of updated method signatures:
```php
// Before (3.x):
public function setConfig($config)
{
    // ...
}

// After (4.0):
public function setConfig(array $config): void
{
    // ...
}
```
