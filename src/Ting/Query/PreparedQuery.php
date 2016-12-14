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

use CCMBenchmark\Ting\Exception;
use CCMBenchmark\Ting\Repository\CollectionInterface;

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
     * Prepare a reading query (SELECT, SHOW, ...)
     * @return $this
     * @throws Exception
     * @throws QueryException
     */
    public function prepareQuery()
    {
        if ($this->prepared === true) {
            return $this;
        }

        if ($this->selectMaster === true) {
            $this->statement = $this->connection->master()->prepare($this->sql);
        } else {
            $this->statement = $this->connection->slave()->prepare($this->sql);
        }
        $this->prepared  = true;

        return $this;
    }

    /**
     * Prepare a writing query (UPDATE, INSERT, DELETE, ...)
     * @return $this
     * @throws Exception
     * @throws QueryException
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
     * Prepare then execute a reading query
     * @param CollectionInterface $collection
     * @return CollectionInterface
     * @throws QueryException
     */
    public function query(CollectionInterface $collection = null)
    {
        if ($collection === null) {
            $collection = $this->collectionFactory->get();
        }

        $this->prepareQuery();

        $this->statement->execute($this->params, $collection);

        return $collection;
    }

    /**
     * Prepare then execute a writing query
     * @return mixed
     * @throws QueryException
     */
    public function execute()
    {
        $this->prepareExecute();

        return $this->statement->execute($this->params);
    }

    public function getStatementName()
    {
        return sha1($this->sql);
    }
}
