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

namespace CCMBenchmark\Ting\Query\Cached;

use CCMBenchmark\Ting\Repository\CollectionInterface;
use CCMBenchmark\Ting\Query\QueryException;

class PreparedQuery extends Query
{

    /**
     * @var int|null
     */
    protected $prepared = false;

    /**
     * @var \CCMBenchmark\Ting\Driver\StatementInterface
     */
    protected $statement = null;

    /**
     * Prepare the query. Only for reading query (SELECT, SHOW, etc.)
     * @return $this
     */
    public function prepareQuery()
    {
        if ($this->prepared === true) {
            return $this;
        }

        $this->statement = $this->connection->slave()->prepare($this->sql);
        $this->prepared  = true;

        return $this;
    }

    /**
     * Prepare the query. Only for writing query (INSERT, UPDATE, DELETE, ...)
     * @return $this
     */
    public function prepareExecute()
    {
        if ($this->prepared === true) {
            return $this;
        }

        $this->statement = $this->connection->master()->prepare($this->sql);
        $this->prepared  = true;

        return $this;
    }

    /**
     * Prepare and execute the read query.
     * @param CollectionInterface $collection
     * @return CollectionInterface
     * @throws QueryException
     */
    public function query(CollectionInterface $collection = null)
    {
        $this->checkTtl();

        if ($collection === null) {
            $collection = $this->collectionFactory->get();
        }

        $isCached = $this->checkCache($this->cacheKey, $collection, $this->params);

        if ($isCached === true) {
            return $collection;
        }

        $this->prepareQuery();

        $this->statement->execute($this->params, $collection);
        $this->cache->save($this->cacheKey, $collection->toCache(), $this->ttl);

        return $collection;
    }

    /**
     * Prepare and execute a writing query
     * @return mixed
     * @throws QueryException
     */
    public function execute()
    {
        $this->prepareExecute();

        return $this->statement->execute($this->params);
    }
}
