<?php

namespace CCMBenchmark\Ting\Serializer;


class Uuid implements SerializerInterface
{
    public function serialize($toSerialize, array $options = [])
    {
        if ($toSerialize === null) {
            return null;
        }
        if (!($toSerialize instanceof \Symfony\Component\Uid\Uuid)) {
            throw new RuntimeException('UUID has to be an instance of \Symfony\Component\Uid\Uuid . ' . gettype($toSerialize) . ' given.');
        }

        return $toSerialize->toRfc4122();
    }

    public function unserialize($serialized, array $options = [])
    {
        if ($serialized === null) {
            return null;
        }
        try {
            return \Symfony\Component\Uid\Uuid::fromString($serialized);
        } catch (\InvalidArgumentException $e) {
            throw new RuntimeException('Cannot convert ' . $serialized . ' to UUID.');
        }
    }
}