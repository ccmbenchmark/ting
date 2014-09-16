<?php

namespace tests\units\fastorm\Entity;

use \mageekguy\atoum;

class RepositoryFactory extends atoum
{
    public function testGet()
    {
        $services = new \fastorm\Services();

        $this
            ->if($repositoryFactory = new \fastorm\Entity\RepositoryFactory(
                $services->get('ConnectionPool'),
                $services->get('MetadataRepository'),
                $services->get('MetadataFactory'),
                $services->get('Collection'),
                $services->get('Hydrator')
            ))
            ->and($repository = $repositoryFactory->get('\mock\tests\fixtures\model\BouhRepository'))
            ->object($repository)
                ->isInstanceOf('\mock\tests\fixtures\model\BouhRepository');
    }
}
