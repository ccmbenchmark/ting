<?php

namespace CCMBenchmark\Ting\Serializer;


class DateTimeZone implements SerializerInterface
{
    public function serialize($toSerialize, array $options = [])
    {
        if ($toSerialize === null) {
            return null;
        }

        if (!($toSerialize instanceof \DateTimeZone)) {
            throw new RuntimeException('datetimezone has to be an instance of \DateTimeZone');
        }

        return $toSerialize->getName();
    }

    public function unserialize($serialized, array $options = [])
    {
        if ($serialized === null) {
            return null;
        }
        try {
            return new \DateTimeZone($serialized);
        } catch (\Exception $e) {
            throw new RuntimeException('Cannot convert ' . $serialized . ' to DateTimeZone.');
        }
    }
}