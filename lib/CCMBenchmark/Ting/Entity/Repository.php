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

use CCMBenchmark\Ting\ConnectionPool;
use CCMBenchmark\Ting\ConnectionPoolInterface;
use CCMBenchmark\Ting\ContainerInterface;
use CCMBenchmark\Ting\Driver\DriverInterface;
use CCMBenchmark\Ting\Exception;
use CCMBenchmark\Ting\Query\PreparedQuery;
use CCMBenchmark\Ting\Query\Query;

class Repository
{

    protected $services = null;
    protected $metadata = null;

    /**
     * @var \CCMBenchmark\Ting\ConnectionPoolInterface
     */
    protected $connectionPool;

    public function __construct(
        ConnectionPool $connectionPool,
        MetadataRepository $metadataRepository,
        MetadataFactoryInterface $metadataFactory,
        Collection $collection,
        Hydrator $hydrator
    ) {
        $this->connectionPool     = $connectionPool;
        $this->metadataRepository = $metadataRepository;
        $this->collection         = $collection;
        $this->hydrator           = $hydrator;

        $class  = get_class($this);
        $this->metadata = $class::initMetadata($metadataFactory);
        $this->metadataRepository->addMetadata($class, $this->metadata);
    }

    public function get(
        $primaryKeyValue,
        Hydrator $hydrator = null,
        Collection $collection = null
    ) {

        if ($hydrator === null) {
            $hydrator = $this->hydrator;
        }

        if ($collection === null) {
            $collection = $this->collection;
        }

        $this->metadata->connect(
            $this->connectionPool,
            function (DriverInterface $driver) use ($collection, $primaryKeyValue) {
                $this->metadata->generateQueryForPrimary(
                    $driver,
                    $primaryKeyValue,
                    function (Query $query) use ($driver, $collection) {
                        $query->setDriver($driver)->execute($collection);
                    }
                );
            }
        );

        $collection->hydrator($hydrator);
        return current($collection->rewind()->current());
    }

    public function execute(Query $query, Collection $collection = null)
    {
        if ($collection === null) {
            $collection = $this->collection;
        }

        $this->metadata->connect(
            $this->connectionPool,
            function (DriverInterface $driver) use ($query, $collection) {
                $query->setDriver($driver)->execute($collection);
            }
        );

        return $collection;
    }

    public function executePrepared(PreparedQuery $query, $collection = null)
    {
        if ($collection === null) {
            $collection = $this->collection;
        }

        $this->metadata->connect(
            $this->connectionPool,
            function (DriverInterface $driver) use ($query, $collection) {
                $query->setDriver($driver)->prepare()->execute($collection);
            }
        );

        return $collection;
    }

    public static function initMetadata(MetadataFactoryInterface $metadataFactory)
    {
        throw new Exception('You should add initMetadata in your class repository');

        /**
         * Example for your repository :
         *
            $metadata = $metadataFactory->get();

            $metadata->setClass(get_called_class());
            $metadata->addField(array(
               'primary'    => true,
               'fieldName'  => 'aField',
               'columnName' => 'COLUMN_NAME',
               'type'       => 'int'
            ));

            return $metadata;
        */
    }

    public function startTransaction()
    {
        $this->metadata->connect(
            $this->connectionPool,
            function (DriverInterface $driver) {
                $driver->startTransaction();
            }
        );
    }

    public function rollback()
    {
        $this->metadata->connect(
            $this->connectionPool,
            function (DriverInterface $driver) {
                $driver->rollback();
            }
        );
    }

    public function commit()
    {
        $this->metadata->connect(
            $this->connectionPool,
            function (DriverInterface $driver) {
                $driver->commit();
            }
        );
    }
}
