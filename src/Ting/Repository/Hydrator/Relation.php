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

namespace CCMBenchmark\Ting\Repository\Hydrator;

abstract class Relation
{
    /**
     * @var AggregateFrom
     */
    private $from;

    /**
     * @var AggregateTo
     */
    private $to;

    /**
     * @var string
     */
    private $setter;

    /**
     * @param AggregateFrom $from
     * @param AggregateTo   $to
     * @param string        $setter
     */
    public function __construct(AggregateFrom $from, AggregateTo $to, $setter)
    {
        $this->from   = $from;
        $this->to     = $to;
        $this->setter = (string) $setter;
    }

    public function getSource(): string
    {
        return $this->from->getTarget();
    }

    public function getTarget(): string
    {
        return $this->to->getTarget();
    }

    public function getSetter(): string
    {
        return $this->setter;
    }
}
