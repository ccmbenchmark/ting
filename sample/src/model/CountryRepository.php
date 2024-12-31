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

namespace sample\src\model;

use CCMBenchmark\Ting\Repository\Metadata;
use CCMBenchmark\Ting\Repository\MetadataInitializer;
use CCMBenchmark\Ting\Serializer\SerializerFactoryInterface;

class CountryRepository extends \CCMBenchmark\Ting\Repository\Repository implements MetadataInitializer
{
    public static function initMetadata(SerializerFactoryInterface $serializerFactory, array $options = [])
    {
        $metadata = new Metadata($serializerFactory);

        $metadata->setEntity('sample\src\model\Country');
        $metadata->setConnectionName('main');
        $metadata->setDatabase('world');
        $metadata->setTable('t_country_cou');

        $metadata->addField([
           'primary'       => true,
           'autoincrement' => true,
           'fieldName'     => 'code',
           'columnName'    => 'cou_code',
            'type'         => 'string'
        ]);

        $metadata->addField([
            'fieldName'  => 'name',
            'columnName' => 'cou_name',
            'type'       => 'string'
        ]);

        $metadata->addField([
            'fieldName'  => 'continent',
            'columnName' => 'cou_continent',
            'type'       => 'string'
        ]);

        $metadata->addField([
            'fieldName'  => 'region',
            'columnName' => 'cou_region',
            'type'       => 'string'
        ]);

        $metadata->addField([
            'fieldName'  => 'president',
            'columnName' => 'cou_head_of_state',
            'type'       => 'string'
        ]);

        return $metadata;
    }
}
