<?php

namespace tests\units\fastorm\Entity;

use \mageekguy\atoum;

class MetadataFactory extends atoum
{
    public function testShouldImplementMetadataFactoryInterface()
    {
        $services = new \fastorm\Services();

        $this
            ->object(new \fastorm\Entity\MetadataFactory($services->get('QueryFactory')))
            ->isInstanceOf('\fastorm\Entity\MetadataFactory');
    }

    public function testGetReturnMetadataInstance()
    {
        $services = new \fastorm\Services();

        $this
            ->if($metadataFactory = new \fastorm\Entity\MetadataFactory($services->get('QueryFactory')))
            ->and($metadata = $metadataFactory->get())
            ->object($metadata)
                ->isInstanceOf('\fastorm\Entity\Metadata');
    }
}
