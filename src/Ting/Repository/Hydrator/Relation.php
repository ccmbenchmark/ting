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
    protected $source;
    protected $sourceIdentifier;
    protected $target;
    protected $targetIdentifier;
    protected $setter;

    public function aggregate($source, $identifier = null)
    {
        $this->source           = (string) $source;

        if ($identifier !== null) {
            $this->sourceIdentifier = (string)$identifier;
        }

        return $this;
    }

    public function to($target, $identifier = null)
    {
        $this->target           = (string) $target;

        if ($identifier !== null) {
            $this->targetIdentifier = (string)$identifier;
        }

        return $this;
    }

    public function setter($setter)
    {
        $this->setter = (string) $setter;

        return $this;
    }

    public function getSource()
    {
        return $this->source;
    }

    public function getSourceIdentifier()
    {
        return $this->sourceIdentifier;
    }

    public function getTarget()
    {
        return $this->target;
    }

    public function getTargetIdentifier()
    {
        return $this->targetIdentifier;
    }

    public function getSetter()
    {
        return $this->setter;
    }
}
