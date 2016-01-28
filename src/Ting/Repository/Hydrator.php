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

use CCMBenchmark\Ting\Driver\ResultInterface;
use CCMBenchmark\Ting\Entity\NotifyProperty;
use CCMBenchmark\Ting\MetadataRepository;
use CCMBenchmark\Ting\Serializer\UnserializeInterface;
use CCMBenchmark\Ting\UnitOfWork;

class Hydrator implements HydratorInterface
{

    protected $mapAliases         = [];
    protected $mapObjects         = [];
    protected $objectDatabase     = [];
    protected $unserializeAliases = [];

    /**
     * @var ResultInterface
     */
    protected $result = null;

    /**
     * @var MetadataRepository
     */
    protected $metadataRepository = null;

    /**
     * @var UnitOfWork
     */
    protected $unitOfWork = null;

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
     * @param ResultInterface $result
     * @return $this
     */
    public function setResult(ResultInterface $result)
    {
        $this->result = $result;
        return $this;
    }

    /**
     * @return \Generator
     */
    public function getIterator()
    {
        foreach ($this->result as $key => $columns) {
            yield $key => $this->hydrateColumns(
                $this->result->getConnectionName(),
                $this->result->getDatabase(),
                $columns
            );
        }
    }

    /**
     * @return int
     */
    public function count()
    {
        if ($this->result === null) {
            return 0;
        }

        return $this->result->getNumRows();
    }

    /**
     * @param string $alias
     * @param UnserializeInterface $unserialize
     * @param array $options
     * @return $this
     */
    public function unserializeAliasWith($alias, UnserializeInterface $unserialize, array $options = [])
    {
        if (isset($this->unserializeAliases[$alias]) === false) {
            $this->unserializeAliases[$alias] = [];
        }
        $this->unserializeAliases[$alias] = [$unserialize, $options];

        return $this;
    }


    /**
     * @param string $from
     * @param string $to
     * @param string $column
     *
     * @return $this
     */
    public function mapAliasTo($from, $to, $column)
    {
        if (isset($this->mapAliases[$to]) === false) {
            $this->mapAliases[$to] = [];
        }
        $this->mapAliases[$to][] = [$from, $column];

        return $this;
    }

    /**
     * @param string $from
     * @param string $to
     * @param string $column
     *
     * @return $this
     */
    public function mapObjectTo($from, $to, $column)
    {
        if (isset($this->mapObjects[$to]) === false) {
            $this->mapObjects[$to] = [];
        }
        $this->mapObjects[$to][] = [$from, $column];

        return $this;
    }


    /**
     * @param string $object
     * @param string $database
     *
     * @return $this
     */
    public function objectDatabaseIs($object, $database)
    {
        $this->objectDatabase[$object] = $database;

        return $this;
    }

    /**
     * Hydrate one object from values
     *
     * @internal hydrate all column into the right Entity according to the table name and metadata information
     *           all virtual columns (COUNT(*), etc) will be set in the array key 0
     *           all Entities without any information (a "LEFT JOIN user" can return no informatoin at all about user)
     *              are set to null
     *
     * @param string $connectionName
     * @param string $database
     * @param array  $columns
     *
     * @return array
     */
    protected function hydrateColumns($connectionName, $database, array $columns)
    {
        $result        = [];
        $metadataList  = [];
        $tmpEntities   = []; // Temporary entity when all properties are null for the moment (LEFT/RIGHT JOIN)
        $validEntities = []; // Entity marked as valid will fill an object
                             // (a valid Entity is a entity with at less one property not null)

        foreach ($columns as $column) {
            // We have the information table, it's not a virtual column like COUNT(*)
            if (isset($result[$column['table']]) === false) {

                if (isset($this->objectDatabase[$column['table']]) === true) {
                    $database = $this->objectDatabase[$column['table']];
                }

                $this->metadataRepository->findMetadataForTable(
                    $connectionName,
                    $database,
                    $column['orgTable'],
                    function (Metadata $metadata) use ($column, &$result, &$metadataList) {
                        $metadataList[$column['table']] = $metadata;
                        $result[$column['table']]       = $metadata->createEntity();
                        $tmpEntities[$column['table']]  = [];
                    }
                );
            }

            // We have a metadata defined for the column
            if (isset($metadataList[$column['table']]) === true &&
                $metadataList[$column['table']]->hasColumn($column['orgName']) === true
            ) {
                // Column value is null or entity is still not marked as valid
                if ($column['value'] === null && isset($validEntities[$column['table']]) === false) {
                    $tmpEntities[$column['table']][$column['orgName']] = $result[$column['table']];
                } else {
                    // Entity was previously marked as a temporary entity, we set all previous columns retrieved
                    if (isset($tmpEntities[$column['table']]) === true && $tmpEntities[$column['table']] !== []) {
                        foreach ($tmpEntities[$column['table']] as $entityColumn => $entity) {
                            $metadataList[$column['table']]->setEntityProperty(
                                $entity,
                                $entityColumn,
                                null
                            );
                        }
                        unset($tmpEntities[$column['table']]);
                    }

                    $validEntities[$column['table']] = true;

                    $metadataList[$column['table']]->setEntityProperty(
                        $result[$column['table']],
                        $column['orgName'],
                        $column['value']
                    );
                }

            // Table is not mapped or column is a virtual column
            } else {
                $validEntities[0] = true;
                if (isset($result[0]) === false) {
                    $result[0] = new \stdClass();
                }

                $result[0]->{$column['name']} = $column['value'];
            }
        }

        // Virtual object
        if (isset($result[0]) === true) {
            foreach ($this->unserializeAliases as $aliasName => list($unserialize, $options)) {
                if (isset($result[0]->$aliasName) === true) {
                    $result[0]->$aliasName = $unserialize->unserialize($result[0]->$aliasName, $options);
                }
            }
        }

        foreach ($result as $table => $entity) {
            // All no valid entity is replaced by a null value
            if (isset($validEntities[$table]) === false) {
                $result[$table] = null;
            }

            if (isset($this->mapAliases[$table]) === true) {
                foreach ($this->mapAliases[$table] as $fromAndColumn) {
                    $entity->{$fromAndColumn[1]}($result[0]->{$fromAndColumn[0]});
                    unset($result[0]->{$fromAndColumn[0]});
                }
            }

            if (isset($this->mapObjects[$table]) === true) {
                foreach ($this->mapObjects[$table] as $fromAndColumn) {
                    if (isset($result[$fromAndColumn[0]]) === true) {
                        $entity->{$fromAndColumn[1]}($result[$fromAndColumn[0]]);
                        unset($result[$fromAndColumn[0]]);
                    }
                }
            }

            if (is_object($entity) === true && ($entity instanceof \stdClass) === false) {
                $this->unitOfWork->manage($entity);
            }
        }

        if (isset($result[0]) === true && get_object_vars($result[0]) === []) {
            unset($result[0]);
        }

        return $result;
    }
}
