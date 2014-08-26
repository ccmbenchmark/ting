<?php

namespace sample\src\model;

use fastorm\PreparedQuery;
use fastorm\Entity\Hydrator;
use fastorm\Entity\Metadata;
use fastorm\Entity\MetadataRepository;

class CityRepository extends \fastorm\Entity\Repository
{

    public function getZCountryWithLotsPopulation()
    {

        $query = new PreparedQuery(
            'select cit_id, cit_name, cou_code, cit_district, cit_population
            from t_city_cit as a where cit_name like :name and cit_population > :population limit 3',
            array('name' => 'Z%', 'population' => '200000')
        );

        return $this->executePrepared($query)->hydrator(new Hydrator());
    }

    public function getNumberOfCities()
    {

        $query = new PreparedQuery(
            "select COUNT(*) AS nb from T_CITY_CIT as a WHERE cit_population > :population",
            ['population' => 20000]
        );

        return $this->executePrepared($query);
    }

    public static function initMetadata(MetadataRepository $metadataRepository = null, Metadata $metadata = null)
    {
        if ($metadataRepository === null) {
            $metadataRepository = MetadataRepository::getInstance();
        }

        if ($metadata === null) {
            $metadata = new Metadata();
        }

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

        $metadata->addInto($metadataRepository);
    }
}
