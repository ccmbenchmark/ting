<?php

namespace sample\src\model;

use fastorm\Entity\Metadata;
use fastorm\Entity\MetadataRepository;

class CountryRepository extends \fastorm\Entity\Repository
{
    public static function initMetadata(\fastorm\ContainerInterface $serviceLocator)
    {
        $metadata = $serviceLocator->get('Metadata');

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
