<?php

namespace tests\units\CCMBenchmark\Ting\Entity;

use \mageekguy\atoum;

class RepositoryFactory extends atoum
{
    public function testGet()
    {
        $services = new \CCMBenchmark\Ting\Services();

        $this
            ->if($repositoryFactory = new \CCMBenchmark\Ting\Entity\RepositoryFactory(
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
