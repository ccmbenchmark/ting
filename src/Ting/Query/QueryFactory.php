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

namespace CCMBenchmark\Ting\Query;

use CCMBenchmark\Ting\Connection;
use Doctrine\Common\Cache\Cache;
use CCMBenchmark\Ting\Repository\CollectionFactoryInterface;

class QueryFactory implements QueryFactoryInterface
{
    /**
     * @param string $sql
     * @param Connection $connection
     * @param CollectionFactoryInterface $collectionFactory
     * @return Query
     */
    public function get($sql, Connection $connection, ?CollectionFactoryInterface $collectionFactory = null)
    {
        return new Query($sql, $connection, $collectionFactory);
    }

    /**
     * @param string $sql
     * @param Connection $connection
     * @param CollectionFactoryInterface $collectionFactory
     * @return PreparedQuery
     */
    public function getPrepared($sql, Connection $connection, ?CollectionFactoryInterface $collectionFactory = null)
    {
        return new PreparedQuery($sql, $connection, $collectionFactory);
    }

    /**
     * @param string $sql
     * @param Connection $connection
     * @param Cache $cache
     * @param CollectionFactoryInterface $collectionFactory
     * @return Cached\Query
     */
    public function getCached(
        $sql,
        Connection $connection,
        Cache $cache,
        ?CollectionFactoryInterface $collectionFactory = null
    ) {
        $cachedQuery = new Cached\Query($sql, $connection, $collectionFactory);
        $cachedQuery->setCache($cache);
        return $cachedQuery;
    }

    /**
     * @param string $sql
     * @param Connection $connection
     * @param Cache $cache
     * @param CollectionFactoryInterface $collectionFactory
     * @return Cached\PreparedQuery
     */
    public function getCachedPrepared(
        $sql,
        Connection $connection,
        Cache $cache,
        ?CollectionFactoryInterface $collectionFactory = null
    ) {
        $cachedPreparedQuery = new Cached\PreparedQuery($sql, $connection, $collectionFactory);
        $cachedPreparedQuery->setCache($cache);
        return $cachedPreparedQuery;
    }
}
