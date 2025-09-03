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

namespace CCMBenchmark\Ting\Driver;

use Iterator;

/**
 * @template T
 *
 * @template-extends Iterator<int, T>
 */
interface ResultInterface extends Iterator
{
    public function setConnectionName(string $connectionName): static;

    public function setDatabase(string $database): static;

    /**
     * @param T $result
     */
    public function setResult($result): static;

    public function getConnectionName(): ?string;

    public function getDatabase(): ?string;

    public function getNumRows(): int;
}
