<?php


namespace CCMBenchmark\Ting\Serializer;


class String implements SerializerInterface
{
    /**
     * @param mixed $toSerialize
     * @param array $options
     * @return string
     */
    public function serialize($toSerialize, array $options = [])
    {
        return (string)$toSerialize;
    }

    /**
     * @param string $serialized
     * @param array  $options
     * @return boolean
     */
    public function unserialize($serialized, array $options = [])
    {
        return (string)$serialized;
    }
}
