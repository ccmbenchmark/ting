<?php

namespace sample\src\model;

use CCMBenchmark\Ting\Entity\Metadata;
use CCMBenchmark\Ting\Entity\MetadataRepository;

class CountryRepository extends \CCMBenchmark\Ting\Entity\Repository
{
    public static function initMetadata(\CCMBenchmark\Ting\ContainerInterface $services)
    {
        $metadata = $services->get('Metadata');

        $metadata->setClass(get_class());
        $metadata->setConnection('main');
        $metadata->setDatabase('world');
        $metadata->setTable('t_country_cou');

        $metadata->addField(array(
           'primary'    => true,
           'fieldName'  => 'code',
           'columnName' => 'cou_code'
        ));

        $metadata->addField(array(
            'fieldName'  => 'name',
            'columnName' => 'cou_name',
        ));

        $metadata->addField(array(
            'fieldName'  => 'continent',
            'columnName' => 'cou_continent',
        ));

        $metadata->addField(array(
            'fieldName'  => 'region',
            'columnName' => 'cou_region',
        ));

        $metadata->addField(array(
            'fieldName'  => 'president',
            'columnName' => 'cou_head_of_state',
        ));

        return $metadata;
    }
}
