<?php

namespace tests\units\CCMBenchmark\Ting\Entity;

use \mageekguy\atoum;

class MetadataFactory extends atoum
{
    public function testShouldImplementMetadataFactoryInterface()
    {
        $services = new \CCMBenchmark\Ting\Services();

        $this
            ->object(new \CCMBenchmark\Ting\Entity\MetadataFactory($services->get('QueryFactory')))
            ->isInstanceOf('\CCMBenchmark\Ting\Entity\MetadataFactory');
    }

    public function testGetReturnMetadataInstance()
    {
        $services = new \CCMBenchmark\Ting\Services();

        $this
            ->if($metadataFactory = new \CCMBenchmark\Ting\Entity\MetadataFactory($services->get('QueryFactory')))
            ->and($metadata = $metadataFactory->get())
            ->object($metadata)
                ->isInstanceOf('\CCMBenchmark\Ting\Entity\Metadata');
    }
}
