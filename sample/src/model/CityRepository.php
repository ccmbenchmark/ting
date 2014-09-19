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

use CCMBenchmark\Ting\Query\PreparedQuery;
use CCMBenchmark\Ting\Repository\Hydrator;
use CCMBenchmark\Ting\Repository\Metadata;

class CityRepository extends \CCMBenchmark\Ting\Repository\Repository
{

    public function getZCountryWithLotsPopulation()
    {

        $query = $this->services
            ->getWithArguments(
                'PreparedQuery',
                [
                    'sql'    => 'select cit_id, cit_name, cou_code, cit_district, cit_population
                        from t_city_cit as a where cit_name like :name and cit_population > :population limit 3',
                    'params' => ['name' => 'Z%', 'population' => 200000]
                ]
            );

        return $this->executePrepared($query)->hydrator(new Hydrator($this->services));
    }

    public function getNumberOfCities()
    {

        $query = $this->services
            ->getWithArguments(
                'PreparedQuery',
                [
                    'sql'    => 'select COUNT(*) AS nb from t_city_cit as a WHERE cit_population > :population',
                    'params' => ['population' => 20000]
                ]
            );

        return $this->executePrepared($query);
    }

    public static function initMetadata(\CCMBenchmark\Ting\ContainerInterface $services)
    {
        $metadata = $services->get('Metadata');

        $metadata->setClass(get_class());
        $metadata->setConnection('main');
        $metadata->setDatabase('world');
        $metadata->setTable('t_city_cit');

        $metadata->addField(array(
            'primary'    => true,
            'fieldName'  => 'id',
            'columnName' => 'cit_id',
            'type'       => 'int'
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

        return $metadata;
    }
}
