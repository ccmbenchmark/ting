<?php

namespace CCMBenchmark\Ting\Entity;

use CCMBenchmark\Ting\ConnectionPool;

class RepositoryFactory
{
    public function __construct(
        ConnectionPool $connectionPool,
        MetadataRepository $metadataRepository,
        MetadataFactoryInterface $metadataFactory,
        Collection $collection,
        Hydrator $hydrator
    ) {
        $this->connectionPool     = $connectionPool;
        $this->metadataRepository = $metadataRepository;
        $this->metadataFactory    = $metadataFactory;
        $this->collection         = $collection;
        $this->hydrator           = $hydrator;
    }

    public function get($repositoryName)
    {
        return new $repositoryName(
            $this->connectionPool,
            $this->metadataRepository,
            $this->metadataFactory,
            $this->collection,
            $this->hydrator
        );
    }
}
