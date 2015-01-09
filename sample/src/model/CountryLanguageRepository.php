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
use CCMBenchmark\Ting\Serializer\SerializerFactoryInterface;

class CountryLanguageRepository extends \CCMBenchmark\Ting\Repository\Repository
{
    public static function initMetadata(SerializerFactoryInterface $serializerFactory)
    {
        $metadata = new Metadata($serializerFactory);

        $metadata->setEntity('sample\src\model\CountryLanguage');
        $metadata->setConnectionName('main');
        $metadata->setDatabase('world');
        $metadata->setTable('t_countrylanguage_col');

        $metadata->addField(array(
            'primary'    => true,
            'fieldName'  => 'code',
            'columnName' => 'cou_code',
            'type'       => 'string'
        ));

        $metadata->addField(array(
            'primary'    => true,
            'fieldName'  => 'language',
            'columnName' => 'col_language',
            'type'       => 'string'
        ));

        $metadata->addField(array(
            'fieldName'  => 'isOfficial',
            'columnName' => 'col_is_official',
            'type'       => 'boolean'
        ));

        $metadata->addField(array(
            'fieldName'  => 'percentage',
            'columnName' => 'col_percentage',
            'type'       => 'double'
        ));

        return $metadata;
    }
}
