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

namespace tests\fixtures\model;

use CCMBenchmark\Ting\Entity\NotifyProperty;
use CCMBenchmark\Ting\Entity\NotifyPropertyInterface;

class Tree implements NotifyPropertyInterface
{
    use NotifyProperty;

    private $id;
    private $parent;
    private $hydratedParent;

    public function __construct(int $id, ?int $parent = null, ?Tree $hydratedParent = null)
    {
        $this->id = $id;
        $this->parent = $parent;
        $this->hydratedParent = $hydratedParent;
    }

    public function getId(): int
    {
        return $this->id;
    }

    public function getParent(): ?int
    {
        return $this->parent;
    }

    public function getHydratedParent(): ?Tree
    {
        return $this->hydratedParent;
    }

    public function __sleep()
    {
        return ['id', 'parent'];
    }
}
