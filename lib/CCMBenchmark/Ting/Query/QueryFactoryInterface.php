<?php

namespace CCMBenchmark\Ting\Query;

interface QueryFactoryInterface
{
    public function get($params = []);
    public function getPrepared($params = []);
}
