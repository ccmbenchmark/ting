<?php

namespace fastorm\Driver;

interface StatementInterface
{

    public function setStatement($statement);
    public function bindParams(array $params);
    public function execute();
    public function getAffectedRows();
    public function getResult();
    public function close();
}
