<?php


namespace CCMBenchmark\Ting\Serializer;


class Double implements SerializerInterface
{
    /**
     * @param mixed $toSerialize
     * @param array $options
     * @return string
     */
    public function serialize($toSerialize, array $options = [])
    {
        return (double)$toSerialize;
    }

    /**
     * @param int $serialized
     * @param array  $options
     * @return boolean
     */
    public function unserialize($serialized, array $options = [])
    {
        return (double)$serialized;
    }
}
