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

use CCMBenchmark\Ting\Repository\Collection;
use CCMBenchmark\Ting\Repository\Metadata;

class CityRepository extends \CCMBenchmark\Ting\Repository\Repository
{

    public function getZCountryWithLotsPopulation()
    {

        $query = $this->getQuery(
            'select cit_id, cit_name, cou_code, cit_district, cit_population, last_modified
                    from t_city_cit as a where cit_name like :name and cit_population > :population limit 3'

        );

        return $query->setParams(['name' => 'Z%', 'population' => 200000])->query(new Collection());
    }

    public function getNumberOfCities()
    {

        $query = $this->getQuery('select COUNT(*) AS nb from t_city_cit as a WHERE cit_population > :population');

        return $query->setParams(['population' => 20000])->query()->current();
    }

    public static function initMetadata()
    {
        $metadata = new Metadata();

        $metadata->setEntity('sample\src\model\City');
        $metadata->setConnectionName('main');
        $metadata->setDatabase('world');
        $metadata->setTable('t_city_cit');

        $metadata->addField(array(
            'primary'       => true,
            'autoincrement' => true,
            'fieldName'     => 'id',
            'columnName'    => 'cit_id',
            'type'          => 'int'
        ));

        $metadata->addField(array(
            'fieldName'  => 'name',
            'columnName' => 'cit_name',
            'type'       => 'string'
        ));

        $metadata->addField(array(
            'fieldName'  => 'countryCode',
            'columnName' => 'cou_code',
            'type'       => 'string'
        ));

        $metadata->addField(array(
            'fieldName'  => 'district',
            'columnName' => 'cit_district',
            'type'       => 'string'
        ));

        $metadata->addField(array(
            'fieldName'  => 'population',
            'columnName' => 'cit_population',
            'type'       => 'int'
        ));

        $metadata->addField(array(
            'fieldName'  => 'dt',
            'columnName' => 'last_modified',
            'type'       => 'datetime'
        ));

        return $metadata;
    }
}
