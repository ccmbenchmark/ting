<?php

namespace tests\fixtures\model;

use CCMBenchmark\Ting\Repository\MetadataInitializer;

abstract class AbstractRepository implements MetadataInitializer
{
    // This abstract class is just here to validate it is not loaded by the MetadataRepository::batchLoadMetadata() method.
}
