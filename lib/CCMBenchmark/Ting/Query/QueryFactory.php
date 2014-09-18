<?php

namespace CCMBenchmark\Ting\Query;

class QueryFactory implements QueryFactoryInterface
{
    public function get($params = [])
    {
        return new Query($params);
    }

    public function getPrepared($params = [])
    {
        return new PreparedQuery($params);
    }
}
