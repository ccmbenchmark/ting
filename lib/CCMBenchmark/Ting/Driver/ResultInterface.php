<?php

namespace CCMBenchmark\Ting\Driver;

interface ResultInterface extends \Iterator
{
    public function dataSeek($offset);
    public function format($data);
}
