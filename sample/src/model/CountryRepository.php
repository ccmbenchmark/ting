<?php

namespace sample\src\model;

use fastorm\Entity\Metadata;
use fastorm\Entity\MetadataRepository;

class CountryRepository extends \fastorm\Entity\Repository
{
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
        $metadata->setTable('T_COUNTRY_COU');

        $metadata->addField(array(
           'id'         => true,
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

        $metadata->addInto($metadataRepository);
    }
}
