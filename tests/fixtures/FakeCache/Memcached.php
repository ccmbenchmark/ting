<?php
/***********************************************************************
 *
 * Ting - PHP Datamapper
 * ==========================================
 *
 * Copyright (C) 2014 CCM Benchmark Group. (http://www.ccmbenchmark.com)
 *
 ***********************************************************************
 *
 * Licensed under the Apache License, Version 2.0 (the "License"); you
 * may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 *
 *     http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or
 * implied. See the License for the specific language governing
 * permissions and limitations under the License.
 *
 **********************************************************************/

namespace fixtures\FakeCache;


class Memcached
{
    public function __construct()
    {

    }

    /**
     * (PECL memcached &gt;= 0.1.0)<br/>
     * Return the result code of the last operation
     * @link http://php.net/manual/en/memcached.getresultcode.php
     * @return int Result code of the last Memcached operation.
     */
    public function getResultCode()
    {

    }

    /**
     * (PECL memcached &gt;= 1.0.0)<br/>
     * Return the message describing the result of the last operation
     * @link http://php.net/manual/en/memcached.getresultmessage.php
     * @return string Message describing the result of the last Memcached operation.
     */
    public function getResultMessage()
    {

    }

    /**
     * (PECL memcached &gt;= 0.1.0)<br/>
     * Retrieve an item
     * @link http://php.net/manual/en/memcached.get.php
     * @param string $key <p>
     * The key of the item to retrieve.
     * </p>
     * @param callable $cache_cb [optional] <p>
     * Read-through caching callback or <b>NULL</b>.
     * </p>
     * @param float $cas_token [optional] <p>
     * The variable to store the CAS token in.
     * </p>
     * @return mixed the value stored in the cache or <b>FALSE</b> otherwise.
     * The <b>Memcached::getResultCode</b> will return
     * <b>Memcached::RES_NOTFOUND</b> if the key does not exist.
     */
    public function get($key, callable $cache_cb = null, &$cas_token = null)
    {

    }

    /**
     * (PECL memcached &gt;= 0.1.0)<br/>
     * Retrieve an item from a specific server
     * @link http://php.net/manual/en/memcached.getbykey.php
     * @param string $server_key <p>
     * The key identifying the server to store the value on or retrieve it from. Instead of hashing on the actual key for the item, we hash on the server key when deciding which memcached server to talk to. This allows related items to be grouped together on a single server for efficiency with multi operations.
     * </p>
     * @param string $key <p>
     * The key of the item to fetch.
     * </p>
     * @param callable $cache_cb [optional] <p>
     * Read-through caching callback or <b>NULL</b>
     * </p>
     * @param float $cas_token [optional] <p>
     * The variable to store the CAS token in.
     * </p>
     * @return mixed the value stored in the cache or <b>FALSE</b> otherwise.
     * The <b>Memcached::getResultCode</b> will return
     * <b>Memcached::RES_NOTFOUND</b> if the key does not exist.
     */
    public function getByKey($server_key, $key, callable $cache_cb = null, &$cas_token = null)
    {

    }

    /**
     * (PECL memcached &gt;= 0.1.0)<br/>
     * Retrieve multiple items
     * @link http://php.net/manual/en/memcached.getmulti.php
     * @param array $keys <p>
     * Array of keys to retrieve.
     * </p>
     * @param array $cas_tokens [optional] <p>
     * The variable to store the CAS tokens for the found items.
     * </p>
     * @param int $flags [optional] <p>
     * The flags for the get operation.
     * </p>
     * @return mixed the array of found items or <b>FALSE</b> on failure.
     * Use <b>Memcached::getResultCode</b> if necessary.
     */
    public function getMulti(array $keys, array &$cas_tokens = null, $flags = null)
    {

    }

    /**
     * (PECL memcached &gt;= 0.1.0)<br/>
     * Retrieve multiple items from a specific server
     * @link http://php.net/manual/en/memcached.getmultibykey.php
     * @param string $server_key <p>
     * The key identifying the server to store the value on or retrieve it from. Instead of hashing on the actual key for the item, we hash on the server key when deciding which memcached server to talk to. This allows related items to be grouped together on a single server for efficiency with multi operations.
     * </p>
     * @param array $keys <p>
     * Array of keys to retrieve.
     * </p>
     * @param string $cas_tokens [optional] <p>
     * The variable to store the CAS tokens for the found items.
     * </p>
     * @param int $flags [optional] <p>
     * The flags for the get operation.
     * </p>
     * @return array the array of found items or <b>FALSE</b> on failure.
     * Use <b>Memcached::getResultCode</b> if necessary.
     */
    public function getMultiByKey($server_key, array $keys, &$cas_tokens = null, $flags = null)
    {

    }

    /**
     * (PECL memcached &gt;= 0.1.0)<br/>
     * Request multiple items
     * @link http://php.net/manual/en/memcached.getdelayed.php
     * @param array $keys <p>
     * Array of keys to request.
     * </p>
     * @param bool $with_cas [optional] <p>
     * Whether to request CAS token values also.
     * </p>
     * @param callable $value_cb [optional] <p>
     * The result callback or <b>NULL</b>.
     * </p>
     * @return bool <b>TRUE</b> on success or <b>FALSE</b> on failure.
     * Use <b>Memcached::getResultCode</b> if necessary.
     */
    public function getDelayed(array $keys, $with_cas = null, callable $value_cb = null)
    {

    }

    /**
     * (PECL memcached &gt;= 0.1.0)<br/>
     * Request multiple items from a specific server
     * @link http://php.net/manual/en/memcached.getdelayedbykey.php
     * @param string $server_key <p>
     * The key identifying the server to store the value on or retrieve it from. Instead of hashing on the actual key for the item, we hash on the server key when deciding which memcached server to talk to. This allows related items to be grouped together on a single server for efficiency with multi operations.
     * </p>
     * @param array $keys <p>
     * Array of keys to request.
     * </p>
     * @param bool $with_cas [optional] <p>
     * Whether to request CAS token values also.
     * </p>
     * @param callable $value_cb [optional] <p>
     * The result callback or <b>NULL</b>.
     * </p>
     * @return bool <b>TRUE</b> on success or <b>FALSE</b> on failure.
     * Use <b>Memcached::getResultCode</b> if necessary.
     */
    public function getDelayedByKey($server_key, array $keys, $with_cas = null, callable $value_cb = null)
    {

    }

    /**
     * (PECL memcached &gt;= 0.1.0)<br/>
     * Fetch the next result
     * @link http://php.net/manual/en/memcached.fetch.php
     * @return array the next result or <b>FALSE</b> otherwise.
     * The <b>Memcached::getResultCode</b> will return
     * <b>Memcached::RES_END</b> if result set is exhausted.
     */
    public function fetch()
    {

    }

    /**
     * (PECL memcached &gt;= 0.1.0)<br/>
     * Fetch all the remaining results
     * @link http://php.net/manual/en/memcached.fetchall.php
     * @return array the results or <b>FALSE</b> on failure.
     * Use <b>Memcached::getResultCode</b> if necessary.
     */
    public function fetchAll()
    {

    }

    /**
     * (PECL memcached &gt;= 0.1.0)<br/>
     * Store an item
     * @link http://php.net/manual/en/memcached.set.php
     * @param string $key <p>
     * The key under which to store the value.
     * </p>
     * @param mixed $value <p>
     * The value to store.
     * </p>
     * @param int $expiration [optional] <p>
     * The expiration time, defaults to 0. See Expiration Times for more info.
     * </p>
     * @return bool <b>TRUE</b> on success or <b>FALSE</b> on failure.
     * Use <b>Memcached::getResultCode</b> if necessary.
     */
    public function set($key, $value, $expiration = null)
    {

    }

    /**
     * (PECL memcached &gt;= 0.1.0)<br/>
     * Store an item on a specific server
     * @link http://php.net/manual/en/memcached.setbykey.php
     * @param string $server_key <p>
     * The key identifying the server to store the value on or retrieve it from. Instead of hashing on the actual key for the item, we hash on the server key when deciding which memcached server to talk to. This allows related items to be grouped together on a single server for efficiency with multi operations.
     * </p>
     * @param string $key <p>
     * The key under which to store the value.
     * </p>
     * @param mixed $value <p>
     * The value to store.
     * </p>
     * @param int $expiration [optional] <p>
     * The expiration time, defaults to 0. See Expiration Times for more info.
     * </p>
     * @return bool <b>TRUE</b> on success or <b>FALSE</b> on failure.
     * Use <b>Memcached::getResultCode</b> if necessary.
     */
    public function setByKey($server_key, $key, $value, $expiration = null)
    {

    }

    /**
     * (PECL memcached &gt;= 2.0.0)<br/>
     * Set a new expiration on an item
     * @link http://php.net/manual/en/memcached.touch.php
     * @param string $key <p>
     * The key under which to store the value.
     * </p>
     * @param int $expiration <p>
     * The expiration time, defaults to 0. See Expiration Times for more info.
     * </p>
     * @return bool <b>TRUE</b> on success or <b>FALSE</b> on failure.
     * Use <b>Memcached::getResultCode</b> if necessary.
     */
    public function touch($key, $expiration)
    {

    }

    /**
     * (PECL memcached &gt;= 2.0.0)<br/>
     * Set a new expiration on an item on a specific server
     * @link http://php.net/manual/en/memcached.touchbykey.php
     * @param string $server_key <p>
     * The key identifying the server to store the value on or retrieve it from. Instead of hashing on the actual key for the item, we hash on the server key when deciding which memcached server to talk to. This allows related items to be grouped together on a single server for efficiency with multi operations.
     * </p>
     * @param string $key <p>
     * The key under which to store the value.
     * </p>
     * @param int $expiration <p>
     * The expiration time, defaults to 0. See Expiration Times for more info.
     * </p>
     * @return bool <b>TRUE</b> on success or <b>FALSE</b> on failure.
     * Use <b>Memcached::getResultCode</b> if necessary.
     */
    public function touchByKey($server_key, $key, $expiration)
    {

    }

    /**
     * (PECL memcached &gt;= 0.1.0)<br/>
     * Store multiple items
     * @link http://php.net/manual/en/memcached.setmulti.php
     * @param array $items <p>
     * An array of key/value pairs to store on the server.
     * </p>
     * @param int $expiration [optional] <p>
     * The expiration time, defaults to 0. See Expiration Times for more info.
     * </p>
     * @return bool <b>TRUE</b> on success or <b>FALSE</b> on failure.
     * Use <b>Memcached::getResultCode</b> if necessary.
     */
    public function setMulti(array $items, $expiration = null)
    {

    }

    /**
     * (PECL memcached &gt;= 0.1.0)<br/>
     * Store multiple items on a specific server
     * @link http://php.net/manual/en/memcached.setmultibykey.php
     * @param string $server_key <p>
     * The key identifying the server to store the value on or retrieve it from. Instead of hashing on the actual key for the item, we hash on the server key when deciding which memcached server to talk to. This allows related items to be grouped together on a single server for efficiency with multi operations.
     * </p>
     * @param array $items <p>
     * An array of key/value pairs to store on the server.
     * </p>
     * @param int $expiration [optional] <p>
     * The expiration time, defaults to 0. See Expiration Times for more info.
     * </p>
     * @return bool <b>TRUE</b> on success or <b>FALSE</b> on failure.
     * Use <b>Memcached::getResultCode</b> if necessary.
     */
    public function setMultiByKey($server_key, array $items, $expiration = null)
    {

    }

    /**
     * (PECL memcached &gt;= 0.1.0)<br/>
     * Compare and swap an item
     * @link http://php.net/manual/en/memcached.cas.php
     * @param float $cas_token <p>
     * Unique value associated with the existing item. Generated by memcache.
     * </p>
     * @param string $key <p>
     * The key under which to store the value.
     * </p>
     * @param mixed $value <p>
     * The value to store.
     * </p>
     * @param int $expiration [optional] <p>
     * The expiration time, defaults to 0. See Expiration Times for more info.
     * </p>
     * @return bool <b>TRUE</b> on success or <b>FALSE</b> on failure.
     * The <b>Memcached::getResultCode</b> will return
     * <b>Memcached::RES_DATA_EXISTS</b> if the item you are trying
     * to store has been modified since you last fetched it.
     */
    public function cas($cas_token, $key, $value, $expiration = null)
    {

    }

    /**
     * (PECL memcached &gt;= 0.1.0)<br/>
     * Compare and swap an item on a specific server
     * @link http://php.net/manual/en/memcached.casbykey.php
     * @param float $cas_token <p>
     * Unique value associated with the existing item. Generated by memcache.
     * </p>
     * @param string $server_key <p>
     * The key identifying the server to store the value on or retrieve it from. Instead of hashing on the actual key for the item, we hash on the server key when deciding which memcached server to talk to. This allows related items to be grouped together on a single server for efficiency with multi operations.
     * </p>
     * @param string $key <p>
     * The key under which to store the value.
     * </p>
     * @param mixed $value <p>
     * The value to store.
     * </p>
     * @param int $expiration [optional] <p>
     * The expiration time, defaults to 0. See Expiration Times for more info.
     * </p>
     * @return bool <b>TRUE</b> on success or <b>FALSE</b> on failure.
     * The <b>Memcached::getResultCode</b> will return
     * <b>Memcached::RES_DATA_EXISTS</b> if the item you are trying
     * to store has been modified since you last fetched it.
     */
    public function casByKey($cas_token, $server_key, $key, $value, $expiration = null)
    {

    }

    /**
     * (PECL memcached &gt;= 0.1.0)<br/>
     * Add an item under a new key
     * @link http://php.net/manual/en/memcached.add.php
     * @param string $key <p>
     * The key under which to store the value.
     * </p>
     * @param mixed $value <p>
     * The value to store.
     * </p>
     * @param int $expiration [optional] <p>
     * The expiration time, defaults to 0. See Expiration Times for more info.
     * </p>
     * @return bool <b>TRUE</b> on success or <b>FALSE</b> on failure.
     * The <b>Memcached::getResultCode</b> will return
     * <b>Memcached::RES_NOTSTORED</b> if the key already exists.
     */
    public function add($key, $value, $expiration = null)
    {

    }

    /**
     * (PECL memcached &gt;= 0.1.0)<br/>
     * Add an item under a new key on a specific server
     * @link http://php.net/manual/en/memcached.addbykey.php
     * @param string $server_key <p>
     * The key identifying the server to store the value on or retrieve it from. Instead of hashing on the actual key for the item, we hash on the server key when deciding which memcached server to talk to. This allows related items to be grouped together on a single server for efficiency with multi operations.
     * </p>
     * @param string $key <p>
     * The key under which to store the value.
     * </p>
     * @param mixed $value <p>
     * The value to store.
     * </p>
     * @param int $expiration [optional] <p>
     * The expiration time, defaults to 0. See Expiration Times for more info.
     * </p>
     * @return bool <b>TRUE</b> on success or <b>FALSE</b> on failure.
     * The <b>Memcached::getResultCode</b> will return
     * <b>Memcached::RES_NOTSTORED</b> if the key already exists.
     */
    public function addByKey($server_key, $key, $value, $expiration = null)
    {

    }

    /**
     * (PECL memcached &gt;= 0.1.0)<br/>
     * Append data to an existing item
     * @link http://php.net/manual/en/memcached.append.php
     * @param string $key <p>
     * The key under which to store the value.
     * </p>
     * @param string $value <p>
     * The string to append.
     * </p>
     * @return bool <b>TRUE</b> on success or <b>FALSE</b> on failure.
     * The <b>Memcached::getResultCode</b> will return
     * <b>Memcached::RES_NOTSTORED</b> if the key does not exist.
     */
    public function append($key, $value)
    {

    }

    /**
     * (PECL memcached &gt;= 0.1.0)<br/>
     * Append data to an existing item on a specific server
     * @link http://php.net/manual/en/memcached.appendbykey.php
     * @param string $server_key <p>
     * The key identifying the server to store the value on or retrieve it from. Instead of hashing on the actual key for the item, we hash on the server key when deciding which memcached server to talk to. This allows related items to be grouped together on a single server for efficiency with multi operations.
     * </p>
     * @param string $key <p>
     * The key under which to store the value.
     * </p>
     * @param string $value <p>
     * The string to append.
     * </p>
     * @return bool <b>TRUE</b> on success or <b>FALSE</b> on failure.
     * The <b>Memcached::getResultCode</b> will return
     * <b>Memcached::RES_NOTSTORED</b> if the key does not exist.
     */
    public function appendByKey($server_key, $key, $value)
    {

    }

    /**
     * (PECL memcached &gt;= 0.1.0)<br/>
     * Prepend data to an existing item
     * @link http://php.net/manual/en/memcached.prepend.php
     * @param string $key <p>
     * The key of the item to prepend the data to.
     * </p>
     * @param string $value <p>
     * The string to prepend.
     * </p>
     * @return bool <b>TRUE</b> on success or <b>FALSE</b> on failure.
     * The <b>Memcached::getResultCode</b> will return
     * <b>Memcached::RES_NOTSTORED</b> if the key does not exist.
     */
    public function prepend($key, $value)
    {

    }

    /**
     * (PECL memcached &gt;= 0.1.0)<br/>
     * Prepend data to an existing item on a specific server
     * @link http://php.net/manual/en/memcached.prependbykey.php
     * @param string $server_key <p>
     * The key identifying the server to store the value on or retrieve it from. Instead of hashing on the actual key for the item, we hash on the server key when deciding which memcached server to talk to. This allows related items to be grouped together on a single server for efficiency with multi operations.
     * </p>
     * @param string $key <p>
     * The key of the item to prepend the data to.
     * </p>
     * @param string $value <p>
     * The string to prepend.
     * </p>
     * @return bool <b>TRUE</b> on success or <b>FALSE</b> on failure.
     * The <b>Memcached::getResultCode</b> will return
     * <b>Memcached::RES_NOTSTORED</b> if the key does not exist.
     */
    public function prependByKey($server_key, $key, $value)
    {

    }

    /**
     * (PECL memcached &gt;= 0.1.0)<br/>
     * Replace the item under an existing key
     * @link http://php.net/manual/en/memcached.replace.php
     * @param string $key <p>
     * The key under which to store the value.
     * </p>
     * @param mixed $value <p>
     * The value to store.
     * </p>
     * @param int $expiration [optional] <p>
     * The expiration time, defaults to 0. See Expiration Times for more info.
     * </p>
     * @return bool <b>TRUE</b> on success or <b>FALSE</b> on failure.
     * The <b>Memcached::getResultCode</b> will return
     * <b>Memcached::RES_NOTSTORED</b> if the key does not exist.
     */
    public function replace($key, $value, $expiration = null)
    {

    }

    /**
     * (PECL memcached &gt;= 0.1.0)<br/>
     * Replace the item under an existing key on a specific server
     * @link http://php.net/manual/en/memcached.replacebykey.php
     * @param string $server_key <p>
     * The key identifying the server to store the value on or retrieve it from. Instead of hashing on the actual key for the item, we hash on the server key when deciding which memcached server to talk to. This allows related items to be grouped together on a single server for efficiency with multi operations.
     * </p>
     * @param string $key <p>
     * The key under which to store the value.
     * </p>
     * @param mixed $value <p>
     * The value to store.
     * </p>
     * @param int $expiration [optional] <p>
     * The expiration time, defaults to 0. See Expiration Times for more info.
     * </p>
     * @return bool <b>TRUE</b> on success or <b>FALSE</b> on failure.
     * The <b>Memcached::getResultCode</b> will return
     * <b>Memcached::RES_NOTSTORED</b> if the key does not exist.
     */
    public function replaceByKey($server_key, $key, $value, $expiration = null)
    {

    }

    /**
     * (PECL memcached &gt;= 0.1.0)<br/>
     * Delete an item
     * @link http://php.net/manual/en/memcached.delete.php
     * @param string $key <p>
     * The key to be deleted.
     * </p>
     * @param int $time [optional] <p>
     * The amount of time the server will wait to delete the item.
     * </p>
     * @return bool <b>TRUE</b> on success or <b>FALSE</b> on failure.
     * The <b>Memcached::getResultCode</b> will return
     * <b>Memcached::RES_NOTFOUND</b> if the key does not exist.
     */
    public function delete($key, $time = 0)
    {

    }

    /**
     * (PECL memcached &gt;= 2.0.0)<br/>
     * Delete multiple items
     * @link http://php.net/manual/en/memcached.deletemulti.php
     * @param array $keys <p>
     * The keys to be deleted.
     * </p>
     * @param int $time [optional] <p>
     * The amount of time the server will wait to delete the items.
     * </p>
     * @return bool <b>TRUE</b> on success or <b>FALSE</b> on failure.
     * The <b>Memcached::getResultCode</b> will return
     * <b>Memcached::RES_NOTFOUND</b> if the key does not exist.
     */
    public function deleteMulti(array $keys, $time = 0)
    {

    }

    /**
     * (PECL memcached &gt;= 0.1.0)<br/>
     * Delete an item from a specific server
     * @link http://php.net/manual/en/memcached.deletebykey.php
     * @param string $server_key <p>
     * The key identifying the server to store the value on or retrieve it from. Instead of hashing on the actual key for the item, we hash on the server key when deciding which memcached server to talk to. This allows related items to be grouped together on a single server for efficiency with multi operations.
     * </p>
     * @param string $key <p>
     * The key to be deleted.
     * </p>
     * @param int $time [optional] <p>
     * The amount of time the server will wait to delete the item.
     * </p>
     * @return bool <b>TRUE</b> on success or <b>FALSE</b> on failure.
     * The <b>Memcached::getResultCode</b> will return
     * <b>Memcached::RES_NOTFOUND</b> if the key does not exist.
     */
    public function deleteByKey($server_key, $key, $time = 0)
    {

    }

    /**
     * (PECL memcached &gt;= 2.0.0)<br/>
     * Delete multiple items from a specific server
     * @link http://php.net/manual/en/memcached.deletemultibykey.php
     * @param string $server_key <p>
     * The key identifying the server to store the value on or retrieve it from. Instead of hashing on the actual key for the item, we hash on the server key when deciding which memcached server to talk to. This allows related items to be grouped together on a single server for efficiency with multi operations.
     * </p>
     * @param array $keys <p>
     * The keys to be deleted.
     * </p>
     * @param int $time [optional] <p>
     * The amount of time the server will wait to delete the items.
     * </p>
     * @return bool <b>TRUE</b> on success or <b>FALSE</b> on failure.
     * The <b>Memcached::getResultCode</b> will return
     * <b>Memcached::RES_NOTFOUND</b> if the key does not exist.
     */
    public function deleteMultiByKey($server_key, array $keys, $time = 0)
    {

    }

    /**
     * (PECL memcached &gt;= 0.1.0)<br/>
     * Increment numeric item's value
     * @link http://php.net/manual/en/memcached.increment.php
     * @param string $key <p>
     * The key of the item to increment.
     * </p>
     * @param int $offset [optional] <p>
     * The amount by which to increment the item's value.
     * </p>
     * @param int $initial_value [optional] <p>
     * The value to set the item to if it doesn't currently exist.
     * </p>
     * @param int $expiry [optional] <p>
     * The expiry time to set on the item.
     * </p>
     * @return int new item's value on success or <b>FALSE</b> on failure.
     */
    public function increment($key, $offset = 1, $initial_value = 0, $expiry = 0)
    {

    }

    /**
     * (PECL memcached &gt;= 0.1.0)<br/>
     * Decrement numeric item's value
     * @link http://php.net/manual/en/memcached.decrement.php
     * @param string $key <p>
     * The key of the item to decrement.
     * </p>
     * @param int $offset [optional] <p>
     * The amount by which to decrement the item's value.
     * </p>
     * @param int $initial_value [optional] <p>
     * The value to set the item to if it doesn't currently exist.
     * </p>
     * @param int $expiry [optional] <p>
     * The expiry time to set on the item.
     * </p>
     * @return int item's new value on success or <b>FALSE</b> on failure.
     */
    public function decrement($key, $offset = 1, $initial_value = 0, $expiry = 0)
    {

    }

    /**
     * (PECL memcached &gt;= 2.0.0)<br/>
     * Increment numeric item's value, stored on a specific server
     * @link http://php.net/manual/en/memcached.incrementbykey.php
     * @param string $server_key <p>
     * The key identifying the server to store the value on or retrieve it from. Instead of hashing on the actual key for the item, we hash on the server key when deciding which memcached server to talk to. This allows related items to be grouped together on a single server for efficiency with multi operations.
     * </p>
     * @param string $key <p>
     * The key of the item to increment.
     * </p>
     * @param int $offset [optional] <p>
     * The amount by which to increment the item's value.
     * </p>
     * @param int $initial_value [optional] <p>
     * The value to set the item to if it doesn't currently exist.
     * </p>
     * @param int $expiry [optional] <p>
     * The expiry time to set on the item.
     * </p>
     * @return int new item's value on success or <b>FALSE</b> on failure.
     */
    public function incrementByKey($server_key, $key, $offset = 1, $initial_value = 0, $expiry = 0)
    {

    }

    /**
     * (PECL memcached &gt;= 2.0.0)<br/>
     * Decrement numeric item's value, stored on a specific server
     * @link http://php.net/manual/en/memcached.decrementbykey.php
     * @param string $server_key <p>
     * The key identifying the server to store the value on or retrieve it from. Instead of hashing on the actual key for the item, we hash on the server key when deciding which memcached server to talk to. This allows related items to be grouped together on a single server for efficiency with multi operations.
     * </p>
     * @param string $key <p>
     * The key of the item to decrement.
     * </p>
     * @param int $offset [optional] <p>
     * The amount by which to decrement the item's value.
     * </p>
     * @param int $initial_value [optional] <p>
     * The value to set the item to if it doesn't currently exist.
     * </p>
     * @param int $expiry [optional] <p>
     * The expiry time to set on the item.
     * </p>
     * @return int item's new value on success or <b>FALSE</b> on failure.
     */
    public function decrementByKey($server_key, $key, $offset = 1, $initial_value = 0, $expiry = 0)
    {

    }

    /**
     * (PECL memcached &gt;= 0.1.0)<br/>
     * Add a server to the server pool
     * @link http://php.net/manual/en/memcached.addserver.php
     * @param string $host <p>
     * The hostname of the memcache server. If the hostname is invalid, data-related
     * operations will set
     * <b>Memcached::RES_HOST_LOOKUP_FAILURE</b> result code.
     * </p>
     * @param int $port <p>
     * The port on which memcache is running. Usually, this is
     * 11211.
     * </p>
     * @param int $weight [optional] <p>
     * The weight of the server relative to the total weight of all the
     * servers in the pool. This controls the probability of the server being
     * selected for operations. This is used only with consistent distribution
     * option and usually corresponds to the amount of memory available to
     * memcache on that server.
     * </p>
     * @return bool <b>TRUE</b> on success or <b>FALSE</b> on failure.
     */
    public function addServer($host, $port, $weight = 0)
    {

    }

    /**
     * (PECL memcached &gt;= 0.1.1)<br/>
     * Add multiple servers to the server pool
     * @link http://php.net/manual/en/memcached.addservers.php
     * @param array $servers
     * @return bool <b>TRUE</b> on success or <b>FALSE</b> on failure.
     */
    public function addServers(array $servers)
    {

    }

    /**
     * (PECL memcached &gt;= 0.1.0)<br/>
     * Get the list of the servers in the pool
     * @link http://php.net/manual/en/memcached.getserverlist.php
     * @return array The list of all servers in the server pool.
     */
    public function getServerList()
    {

    }

    /**
     * (PECL memcached &gt;= 0.1.0)<br/>
     * Map a key to a server
     * @link http://php.net/manual/en/memcached.getserverbykey.php
     * @param string $server_key <p>
     * The key identifying the server to store the value on or retrieve it from. Instead of hashing on the actual key for the item, we hash on the server key when deciding which memcached server to talk to. This allows related items to be grouped together on a single server for efficiency with multi operations.
     * </p>
     * @return array an array containing three keys of host,
     * port, and weight on success or <b>FALSE</b>
     * on failure.
     * Use <b>Memcached::getResultCode</b> if necessary.
     */
    public function getServerByKey($server_key)
    {

    }

    /**
     * (PECL memcached &gt;= 2.0.0)<br/>
     * Clears all servers from the server list
     * @link http://php.net/manual/en/memcached.resetserverlist.php
     * @return bool <b>TRUE</b> on success or <b>FALSE</b> on failure.
     */
    public function resetServerList()
    {

    }

    /**
     * (PECL memcached &gt;= 2.0.0)<br/>
     * Close any open connections
     * @link http://php.net/manual/en/memcached.quit.php
     * @return bool <b>TRUE</b> on success or <b>FALSE</b> on failure.
     */
    public function quit()
    {

    }

    /**
     * (PECL memcached &gt;= 0.1.0)<br/>
     * Get server pool statistics
     * @link http://php.net/manual/en/memcached.getstats.php
     * @return array Array of server statistics, one entry per server.
     */
    public function getStats()
    {

    }

    /**
     * (PECL memcached &gt;= 0.1.5)<br/>
     * Get server pool version info
     * @link http://php.net/manual/en/memcached.getversion.php
     * @return array Array of server versions, one entry per server.
     */
    public function getVersion()
    {

    }

    /**
     * (PECL memcached &gt;= 2.0.0)<br/>
     * Gets the keys stored on all the servers
     * @link http://php.net/manual/en/memcached.getallkeys.php
     * @return array the keys stored on all the servers on success or <b>FALSE</b> on failure.
     */
    public function getAllKeys()
    {

    }

    /**
     * (PECL memcached &gt;= 0.1.0)<br/>
     * Invalidate all items in the cache
     * @link http://php.net/manual/en/memcached.flush.php
     * @param int $delay [optional] <p>
     * Numer of seconds to wait before invalidating the items.
     * </p>
     * @return bool <b>TRUE</b> on success or <b>FALSE</b> on failure.
     * Use <b>Memcached::getResultCode</b> if necessary.
     */
    public function flush($delay = 0)
    {

    }

    /**
     * (PECL memcached &gt;= 0.1.0)<br/>
     * Retrieve a Memcached option value
     * @link http://php.net/manual/en/memcached.getoption.php
     * @param int $option <p>
     * One of the Memcached::OPT_* constants.
     * </p>
     * @return mixed the value of the requested option, or <b>FALSE</b> on
     * error.
     */
    public function getOption($option)
    {

    }

    /**
     * (PECL memcached &gt;= 0.1.0)<br/>
     * Set a Memcached option
     * @link http://php.net/manual/en/memcached.setoption.php
     * @param int $option
     * @param mixed $value
     * @return bool <b>TRUE</b> on success or <b>FALSE</b> on failure.
     */
    public function setOption($option, $value)
    {

    }

    /**
     * (PECL memcached &gt;= 2.0.0)<br/>
     * Set Memcached options
     * @link http://php.net/manual/en/memcached.setoptions.php
     * @param array $options <p>
     * An associative array of options where the key is the option to set and
     * the value is the new value for the option.
     * </p>
     * @return bool <b>TRUE</b> on success or <b>FALSE</b> on failure.
     */
    public function setOptions(array $options)
    {

    }

    /**
     * (PECL memcached &gt;= 2.0.0)<br/>
     * Check if a persitent connection to memcache is being used
     * @link http://php.net/manual/en/memcached.ispersistent.php
     * @return bool true if Memcache instance uses a persistent connection, false otherwise.
     */
    public function isPersistent()
    {

    }

    /**
     * (PECL memcached &gt;= 2.0.0)<br/>
     * Check if the instance was recently created
     * @link http://php.net/manual/en/memcached.ispristine.php
     * @return bool the true if instance is recently created, false otherwise.
     */
    public function isPristine()
    {

    }
}
