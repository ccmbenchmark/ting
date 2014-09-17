<?php

namespace fastorm\Query;

interface QueryFactoryInterface
{
    public function get($params = []);
    public function getPrepared($params = []);
}
