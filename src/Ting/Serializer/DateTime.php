<?php


namespace CCMBenchmark\Ting\Serializer;


class DateTime implements SerializerInterface
{
    /**
     * @param \DateTime $toSerialize
     * @param array $options
     * @return string
     */
    public function serialize($toSerialize, array $options = [])
    {
        return $toSerialize->format('Y-m-d H:i:s');
    }

    /**
     * @param string $serialized
     * @param array  $options
     * @return \Datetime
     */
    public function unserialize($serialized, array $options = [])
    {
        return \DateTime::createFromFormat('Y-m-d H:i:s', $serialized);
    }
}
