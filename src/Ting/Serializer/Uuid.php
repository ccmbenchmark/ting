<?php

namespace CCMBenchmark\Ting\Serializer;


class Uuid implements SerializerInterface
{
    public function serialize($toSerialize, array $options = [])
    {
        if (!($toSerialize instanceof \Symfony\Component\Uid\Uuid)) {
            throw new RuntimeException('UUID has to be an instance of \Symfony\Component\Uid\Uuid');
        }
        
        return $toSerialize->toRfc4122();
    }

    public function unserialize($serialized, array $options = [])
    {
        return \Symfony\Component\Uid\Uuid::fromString($serialized);
    }
}