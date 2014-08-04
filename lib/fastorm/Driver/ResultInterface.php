<?php

namespace fastorm\Driver;

interface ResultInterface
{

    public function dataSeek($offset);
    public function fetchArray();
    public function fetchFields();
}
