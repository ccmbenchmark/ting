<?php

namespace CCMBenchmark\Ting\Serializer;

class DateTimeZone implements SerializerInterface
{
    public function serialize($toSerialize, array $options = []): ?string
    {
        if ($toSerialize === null) {
            return null;
        }

        if (!($toSerialize instanceof \DateTimeZone)) {
            throw new RuntimeException('datetimezone has to be an instance of \DateTimeZone');
        }

        return $toSerialize->getName();
    }

    /**
     * @param mixed $serialized
     * @param array $options
     * @return \DateTimeZone|null
     */
    public function unserialize(mixed $serialized, array $options = []): ?\DateTimeZone
    {
        if ($serialized === null) {
            return null;
        }
        try {
            return new \DateTimeZone($serialized);
        } catch (\Exception) {
            throw new RuntimeException('Cannot convert ' . $serialized . ' to DateTimeZone.');
        }
    }
}
