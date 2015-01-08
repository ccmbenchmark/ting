<?php


namespace CCMBenchmark\Ting\Driver\Mysqli\Serializer;


use CCMBenchmark\Ting\Serializer\SerializerInterface;

class Bool implements SerializerInterface
{
    /**
     * @param mixed $toSerialize
     * @param array $options
     * @return int|null
     */
    public function serialize($toSerialize, array $options = [])
    {
        if ($toSerialize === true) {
            return 1;
        }
        if ($toSerialize === false) {
            return 0;
        }

        return null;
    }

    /**
     * @param string $serialized
     * @param array  $options
     * @return bool|null
     */
    public function unserialize($serialized, array $options = [])
    {
        if ($serialized === 1) {
            return true;
        }
        if ($serialized === 0) {
            return false;
        }

        return null;
    }
}
