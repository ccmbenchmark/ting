<?php

namespace tests\fixtures\model;

use CCMBenchmark\Ting\Repository\Metadata;
use CCMBenchmark\Ting\Repository\MetadataInitializer;
use CCMBenchmark\Ting\Serializer\SerializerFactoryInterface;

class CityWithPublicPropertiesRepository implements MetadataInitializer
{
    public static function initMetadata(SerializerFactoryInterface $serializerFactory, array $options = []): Metadata
    {
        $metadata = new Metadata($serializerFactory);
        $metadata->setTable('T_CITY_PUB');
        $metadata->setEntity('tests\fixtures\model\CityWithPublicProperties');
        $metadata->setConnectionName('main');
        $metadata->setDatabase('bouh_world');
        $metadata->addField([
            'primary'    => true,
            'fieldName'  => 'id',
            'columnName' => 'id',
            'type'       => 'int'
        ]);
        $metadata->addField([
            'fieldName'  => 'name',
            'columnName' => 'name',
            'type'       => 'string'
        ]);
        return $metadata;
    }
}
