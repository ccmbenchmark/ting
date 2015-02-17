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
     * @param MetadataRepository $metadataRepository
     * @return void
     */
    public function setMetadataRepository(MetadataRepository $metadataRepository)
    {
        $this->metadataRepository = $metadataRepository;
    }

    /**
     * @param UnitOfWork $unitOfWork
     * @return void
     */
    public function setUnitOfWork(UnitOfWork $unitOfWork)
    {
        $this->unitOfWork = $unitOfWork;
    }


    /**
     * Hydrate one object from values and add to Collection
     * @param array               $columns
     * @param CollectionInterface $collection
     * @return array
     */
    public function hydrate(array $columns, CollectionInterface $collection)
    {
        $result = $this->hydrateColumns($columns);
        $collection->add($result);
        return $result;
    }

    /**
     * Hydrate one object from values
     * @param array               $columns
     * @return array
     */
    protected function hydrateColumns(array $columns)
    {
        $result        = [];
        $metadataList  = [];
        $tmpEntities   = [];
        $validEntities = [];

        foreach ($columns as $column) {
            if (isset($result[$column['table']]) === false) {
                $this->metadataRepository->findMetadataForTable(
                    $column['orgTable'],
                    function (Metadata $metadata) use ($column, &$result, &$metadataList) {
                        $metadataList[$column['table']] = $metadata;
                        $result[$column['table']]       = $metadata->createEntity();
                        $tmpEntities[$column['table']]  = [];
                    }
                );
            }

            if (
                isset($metadataList[$column['table']]) === true &&
                $metadataList[$column['table']]->hasColumn($column['orgName']) === true
            ) {
                if ($column['value'] === null && isset($validEntities[$column['table']]) === false) {
                    $tmpEntities[$column['table']][$column['orgName']] = $result[$column['table']];
                } else {
                    if (isset($tmpEntities[$column['table']]) === true && $tmpEntities[$column['table']] !== []) {
                        foreach ($tmpEntities[$column['table']] as $entityColumn => $entity) {
                            $metadataList[$column['table']]->setEntityProperty(
                                $entity,
                                $entityColumn,
                                null
                            );
                        }
                    }

                    $validEntities[$column['table']] = true;

                    $metadataList[$column['table']]->setEntityProperty(
                        $result[$column['table']],
                        $column['orgName'],
                        $column['value']
                    );
                }
            } else {
                $validEntities[0] = true;
                if (isset($result[0]) === false) {
                    $result[0] = new \stdClass();
                }

                $result[0]->$column['name'] = $column['value'];
            }
        }

        foreach ($result as $table => $entity) {
            if (isset($validEntities[$table]) === false) {
                $result[$table] = null;
            }

            if (is_object($entity) === true) {
                $this->unitOfWork->manage($entity);
            }
        }

        return $result;
    }
}
