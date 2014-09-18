<?php

namespace tests\fixtures\model;

use CCMBenchmark\Ting\ContainerInterface;
use CCMBenchmark\Ting\Entity\MetadataFactoryInterface;
use CCMBenchmark\Ting\Entity\MetadataRepository;

class BouhRepository extends \CCMBenchmark\Ting\Entity\Repository
{
    public static function initMetadata(MetadataFactoryInterface $metadataFactory)
    {
        $metadata = $metadataFactory->get();

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

        return $metadata;
    }
}
