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

namespace CCMBenchmark\Ting\Entity;

use CCMBenchmark\Ting\ContainerInterface;
use CCMBenchmark\Ting\NotifyPropertyInterface;
use CCMBenchmark\Ting\PropertyListenerInterface;
use CCMBenchmark\Ting\UnitOfWork;
use CCMBenchmark\Ting\Entity\Metadata;
use CCMBenchmark\Ting\Entity\MetadataRepository;

class Hydrator
{

    protected $metadataRepository = null;
    protected $unitOfWork         = null;

    public function __construct(MetadataRepository $metadaRepository, UnitOfWork $unitOfWork)
    {
        $this->metadataRepository = $metadaRepository;
        $this->unitOfWork         = $unitOfWork;
    }

    public function hydrate($columns = array())
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
                    },
                    function () use (&$result, $column) {
                        $result[$column['table']] = new \stdClass();
                    }
                );
            }

            if (isset($metadataList[$column['table']]) === true
                && $metadataList[$column['table']]->hasColumn($column['orgName'])
            ) {
                $metadataList[$column['table']]->setEntityProperty(
                    $result[$column['table']],
                    $column['orgName'],
                    $column['value']
                );
            } else {
                if (isset($result['db__table']) === false) {
                    $result['db__table'] = new \stdClass();
                }

                $result['db__table']->$column['name'] = $column['value'];
            }
        }

        foreach ($result as $entity) {
            $this->unitOfWork->manage($entity);
        }

        return $result;
    }
}
