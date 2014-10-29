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
    protected $prepared = null;

    /**
     * @var \CCMBenchmark\Ting\Driver\StatementInterface
     */
    protected $statement = null;

    /**
     * @return $this
     */
    protected function prepareQuery()
    {
        if ($this->prepared !== null) {
            return $this;
        }
        
        $this->statement = $this->connection->onSlaveDoPrepare($this->sql);
        $this->prepared  = self::TYPE_RESULT;

        return $this;
    }

    /**
     * @return $this
     */
    protected function prepareExecute()
    {
        if ($this->prepared !== null) {
            return $this;
        }

        $this->statement = $this->connection->onMasterDoPrepare($this->sql);
        $this->prepared  = self::TYPE_UPDATE;

        return $this;
    }

    /**
     * @param array $params
     * @param CollectionInterface $collection
     * @return CollectionInterface
     * @throws QueryException
     */
    public function query(array $params, CollectionInterface $collection = null)
    {
        if ($collection === null) {
            $collection = $this->collectionFactory->get();
        }

        $key      = $this->getCacheKey($params);
        $isCached = $this->checkCache($key, $collection, $params);
        if ($isCached === true) {
            return $collection;
        }

        $this->prepareQuery();

        $this->statement->execute($params, $collection);
        $this->cache->store($key, $collection->toArray(), $this->ttl);

        return $collection;
    }

    /**
     * @param array $params
     * @return mixed
     * @throws QueryException
     */
    public function execute(array $params)
    {
        $this->checkTtl();
        $this->prepareExecute();

        return $this->statement->execute($params);
    }
}
