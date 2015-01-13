<?php


namespace CCMBenchmark\Ting\Driver\Pgsql\Serializer;


use CCMBenchmark\Ting\Serializer\SerializerInterface;

class Bool implements SerializerInterface
{
    /**
     * @param mixed $toSerialize
     * @param array $options
     * @return string|null
     */
    public function serialize($toSerialize, array $options = [])
    {
        if ($toSerialize === true) {
            return 't';
        }
        if ($toSerialize === false) {
            return 'f';
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
        if ($serialized === 't') {
            return true;
        }
        if ($serialized === 'f') {
            return false;
        }

        return null;
    }
}
