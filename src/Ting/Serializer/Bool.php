<?php


namespace CCMBenchmark\Ting\Serializer;


class Bool implements SerializerInterface
{
    /**
     * @param mixed $toSerialize
     * @param array $options
     * @return string
     */
    public function serialize($toSerialize, array $options = [])
    {
        return (bool)$toSerialize;
    }

    /**
     * @param string $serialized
     * @param array  $options
     * @return boolean
     */
    public function unserialize($serialized, array $options = [])
    {
        return (bool)$serialized;
    }
}
