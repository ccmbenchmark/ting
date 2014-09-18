<?php

namespace sample\src\model;

use CCMBenchmark\Ting\Query\PreparedQuery;
use CCMBenchmark\Ting\Entity\Hydrator;
use CCMBenchmark\Ting\Entity\Metadata;
use CCMBenchmark\Ting\Entity\MetadataRepository;

class CityRepository extends \CCMBenchmark\Ting\Entity\Repository
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
