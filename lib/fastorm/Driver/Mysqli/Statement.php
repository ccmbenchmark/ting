<?php

namespace fastorm\Driver\Mysqli;

use fastorm\Driver\Exception;
use fastorm\Driver\QueryException;
use fastorm\Entity\Collection;

class Statement implements \fastorm\Driver\StatementInterface
{

    protected $driverStatement = null;

    /**
     * @return bool
     */
    public function execute($driverStatement, $params, $paramsOrder, Collection $collection)
    {
        $this->driverStatement = $driverStatement;

        $types = '';
        $values = array();
        foreach (array_keys($paramsOrder) as $key) {
            switch ($params[$key]['type']) {
                case 'int':
                case 'integer':
                    $type = 'i';
                    break;
                case 'float':
                    $type = 'd';
                    break;
                case 'blob':
                    $type = 'b';
                    break;
                default:
                    $type = 's';
            }

            $types .= $type;
            $values[] = &$params[$key]['value'];

        }

        array_unshift($values, $types);
        call_user_func_array(array($driverStatement, 'bind_param'), $values);

        $driverStatement->execute();
        $this->setCollectionWithResult($driverStatement, $collection);

        return $this;
    }

    /**
     * @throws \fastorm\Adapter\Driver\QueryException
     */
    public function setCollectionWithResult($driverStatment, Collection $collection)
    {

        $result = $driverStatment->get_result();
        if ($result === false) {
            throw new QueryException($driverStatment->error, $driverStatment->errno);
        }

        $collection->set(new Result($result));
        return $this;
    }

    public function close()
    {
        if ($this->driverStatement === null) {
            throw new Exception('statement->close can\'t be called before statement->execute');
        }

        $this->driverStatement->close();
    }
}
