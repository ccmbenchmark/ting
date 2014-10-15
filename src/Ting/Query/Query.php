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

use CCMBenchmark\Ting\Repository\Collection;

class Query extends QueryAbstract
{

    /**
     * @param Collection $collection
     * @return mixed
     * @throws QueryException
     */
    public function execute(Collection $collection = null)
    {
        if ($this->driver === null) {
            throw new QueryException('You have to set the driver before to call execute');
        }

        if ($collection === null && $this->queryType == QueryAbstract::TYPE_RESULT) {
            $collection = new Collection();
        }

        return $this->driver->execute($this->sql, $this->params, $this->queryType, $collection);
    }
}
