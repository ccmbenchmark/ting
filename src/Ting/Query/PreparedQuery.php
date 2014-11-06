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

use CCMBenchmark\Ting\Repository\CollectionInterface;

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
    public function prepareQuery()
    {
        if ($this->prepared !== null) {
            return $this;
        }

        if ($this->selectMaster === true) {
            $this->statement = $this->connection->master()->prepare($this->sql);
        } else {
            $this->statement = $this->connection->slave()->prepare($this->sql);
        }
        $this->prepared  = self::TYPE_RESULT;

        return $this;
    }

    /**
     * @return $this
     */
    public function prepareExecute()
    {
        if ($this->prepared !== null) {
            return $this;
        }

        $this->statement = $this->connection->master()->prepare($this->sql);
        $this->prepared  = self::TYPE_UPDATE;

        return $this;
    }

    /**
     * @param CollectionInterface $collection
     * @return CollectionInterface
     * @throws QueryException
     */
    public function query(CollectionInterface $collection = null)
    {
        if ($collection === null) {
            $collection = $this->collectionFactory->get();
        }

        if ($this->prepared !== self::TYPE_RESULT) {
            throw new QueryException("You should call prepareQuery to use query method");
        }

        $this->statement->execute($this->params, $collection);

        return $collection;
    }

    /**
     * @return mixed
     * @throws QueryException
     */
    public function execute()
    {
        if ($this->prepared !== self::TYPE_UPDATE) {
            throw new QueryException("You should call prepareExecute to use query method");
        }

        return $this->statement->execute($this->params);
    }
}
