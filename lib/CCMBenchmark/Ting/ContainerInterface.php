<?php

namespace CCMBenchmark\Ting;

interface ContainerInterface
{
    public function get($id);
    public function getWithArguments($id, $args);
    public function has($id);
}
