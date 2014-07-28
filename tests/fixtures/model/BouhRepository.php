<?php

namespace tests\fixtures\model;

class BouhRepository extends \fastorm\Entity\Repository
{
    public static function loadMetadata(\fastorm\Entity\Metadata $metadata)
    {

        $metadata->setConnection('main');
        $metadata->setDatabase('bouh_world');
        $metadata->setTable('T_BOUH_BOO');

        $metadata->addField(array(
           'id'         => true,
           'fieldName'  => 'id',
           'columnName' => 'boo_id'
        ));

        return $metadata;
    }
}
