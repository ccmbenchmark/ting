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

namespace CCMBenchmark\Ting\Repository;

use CCMBenchmark\Ting\MetadataRepository;
use CCMBenchmark\Ting\UnitOfWork;

class Hydrator implements HydratorInterface
{

    protected $metadataRepository = null;
    protected $unitOfWork         = null;

    /**
     * @param MetadataRepository $metadaRepository
     * @param UnitOfWork         $unitOfWork
     */
    public function __construct(MetadataRepository $metadaRepository, UnitOfWork $unitOfWork)
    {
        $this->metadataRepository = $metadaRepository;
        $this->unitOfWork         = $unitOfWork;
    }

    /**
     * Hydrate one object from values
     * @param array               $columns
     * @param CollectionInterface $collection
     * @return array
     */
    public function hydrate(array $columns, CollectionInterface $collection)
    {
        $result       = array();
        $metadataList = array();
        foreach ($columns as $column) {
            if (isset($result[$column['table']]) === false) {
                $this->metadataRepository->findMetadataForTable(
                    $column['orgTable'],
                    function (Metadata $metadata) use ($column, &$result, &$metadataList) {
                        $metadataList[$column['table']] = $metadata;
                        $result[$column['table']]       = $metadata->createEntity();
                    }
                );
            }

            if (
                isset($metadataList[$column['table']]) === true &&
                $metadataList[$column['table']]->hasColumn($column['orgName']) === true
            ) {
                $metadataList[$column['table']]->setEntityProperty(
                    $result[$column['table']],
                    $column['orgName'],
                    $column['value']
                );
            } else {
                if (isset($result[0]) === false) {
                    $result[0] = new \stdClass();
                }

                $result[0]->$column['name'] = $column['value'];
            }
        }

        foreach ($result as $entity) {
            if (is_object($entity) === true) {
                $this->unitOfWork->manage($entity);
            }
        }

        $collection->add($result);

        return $result;
    }
}
