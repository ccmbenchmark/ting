<?php

namespace fastorm\Driver\Mysqli;

use fastorm\Driver\Exception;
use fastorm\Driver\QueryException;
use fastorm\Entity\Collection;

class Statement implements \fastorm\Driver\StatementInterface
{

    const TYPE_RESULT   = 1;
    const TYPE_AFFECTED = 2;
    const TYPE_INSERT   = 3;

    protected $driverStatement = null;
    protected $queryType       = null;

    /**
     * @throws \fastorm\Adapter\Driver\Exception
     */
    public function setQueryType($type)
    {
        if (in_array($type, array(self::TYPE_RESULT, self::TYPE_AFFECTED, self::TYPE_INSERT)) === false) {
            throw new Exception('setQueryType should use one of constant Statement::TYPE_*');
        }

        $this->queryType = $type;
    }

    /**
     * @return bool|int
     */
    public function execute($driverStatement, $params, $paramsOrder, Collection $collection = null)
    {
        $this->driverStatement = $driverStatement;
        $types = '';
        $values = array();
        foreach (array_keys($paramsOrder) as $key) {
            switch (gettype($params[$key])) {
                case "integer":
                    $type = "i";
                    break;
                case "double":
                    $type = "d";
                    break;
                default:
                    $type = "s";
            }
            $types .= $type;
            $values[] = &$params[$key];

        }

        array_unshift($values, $types);
        call_user_func_array(array($driverStatement, 'bind_param'), $values);

        $driverStatement->execute();

        return $this->setCollectionWithResult($driverStatement, $collection);
    }

    /**
     * @throws \fastorm\Adapter\Driver\QueryException
     */
    public function setCollectionWithResult($driverStatement, Collection $collection = null)
    {
        if ($this->queryType !== self::TYPE_RESULT) {
            if ($this->queryType === self::TYPE_INSERT) {
                    return $driverStatement->insert_id;
            }

            $result = $driverStatement->affected_rows;

            if ($result === null || $result === -1) {
                return false;
            }
            return $result;
        }

        $result = $driverStatement->get_result();

        if ($result === false) {
            throw new QueryException($driverStatement->error, $driverStatement->errno);
        }

        $collection->set(new Result($result));
        return true;
    }

    /**
     * @throws \fastorm\Driver\Exception
     */
    public function close()
    {
        if ($this->driverStatement === null) {
            throw new Exception('statement->close can\'t be called before statement->execute');
        }

        $this->driverStatement->close();
    }
}
