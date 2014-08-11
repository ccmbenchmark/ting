<?php

namespace tests\fixtures\model;

use fastorm\Entity\Metadata;
use fastorm\Entity\MetadataRepository;

class BouhRepository extends \fastorm\Entity\Repository
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
        $metadata->setDatabase('bouh_world');
        $metadata->setTable('T_BOUH_BOO');

        $metadata->addField(array(
           'primary'    => true,
           'fieldName'  => 'id',
           'columnName' => 'boo_id'
        ));

        $metadata->addField(array(
           'fieldName'  => 'firstname',
           'columnName' => 'boo_firstname'
        ));

        $metadata->addField(array(
           'fieldName'  => 'name',
           'columnName' => 'boo_name'
        ));

        $metadata->addInto($metadataRepository);
    }
}
