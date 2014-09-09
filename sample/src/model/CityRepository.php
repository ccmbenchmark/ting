<?php

namespace sample\src\model;

use fastorm\Query\PreparedQuery;
use fastorm\Entity\Hydrator;
use fastorm\Entity\Metadata;
use fastorm\Entity\MetadataRepository;

class CityRepository extends \fastorm\Entity\Repository
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
                ]);

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
                ]);

        return $this->executePrepared($query);
    }

    public static function initMetadata(\fastorm\ContainerInterface $services)
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
