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

use CCMBenchmark\Ting\Repository\Metadata;
use CCMBenchmark\Ting\Repository\MetadataInitializer;
use CCMBenchmark\Ting\Serializer\SerializerFactoryInterface;
use CCMBenchmark\Ting\Repository\Repository;

class ParkRepository extends Repository implements MetadataInitializer
{
    public static $options;

    /**
     * @param SerializerFactoryInterface $serializerFactory
     * @param array                      $options
     *
     * @return Metadata
     */
    public static function initMetadata(SerializerFactoryInterface $serializerFactory, array $options = [])
    {
        self::$options = $options;

        $metadata = new Metadata($serializerFactory);

        $metadata->setEntity('tests\fixtures\model\Park');
        $metadata->setConnectionName('connectionName');
        $metadata->setDatabase('database');
        $metadata->setTable('T_PARK_PA');

        $metadata->addField([
            'primary'       => true,
            'autoincrement' => true,
            'fieldName'     => 'id',
            'columnName'    => 'pa_id',
            'type'          => 'int'
        ]);

        $metadata->addField([
            'fieldName'  => 'name',
            'columnName' => 'pa_name',
            'type'       => 'string'
        ]);

        return $metadata;
    }
}
