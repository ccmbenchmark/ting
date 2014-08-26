<?php

namespace fastorm\Driver\Pgsql;

use fastorm\Driver\Exception;
use fastorm\Driver\QueryException;
use fastorm\Driver\StatementInterface;
use fastorm\Entity\Collection;
use fastorm\Query\Query;

class Statement implements StatementInterface
{

    protected $connection    = null;
    protected $statementName = null;
    protected $queryType     = null;
    protected $query         = null;


    public function setConnection($connection)
    {
        $this->connection = $connection;
    }

    public function setQuery($query)
    {
        $this->query = (string) $query;
    }

    /**
     * @throws \fastorm\Adapter\Driver\Exception
     */
    public function setQueryType($type)
    {
        if (in_array($type, array(Query::TYPE_RESULT, Query::TYPE_AFFECTED, Query::TYPE_INSERT)) === false) {
            throw new Exception('setQueryType should use one of constant Statement::TYPE_*');
        }

        $this->queryType = $type;
    }

    /**
     * @param $statementName
     * @param $params
     * @param $paramsOrder
     * @param Collection $collection
     * @return bool|int
     */
    public function execute($statementName, $params, $paramsOrder, Collection $collection = null)
    {
        $this->statementName = $statementName;
        $values = array();
        foreach (array_keys($paramsOrder) as $key) {
            $values[] = &$params[$key];
        }

        $result = pg_execute($this->connection, $statementName, $values);
        return $this->setCollectionWithResult($result, $collection);
    }

    /**
     * @throws \fastorm\Adapter\Driver\QueryException
     */
    public function setCollectionWithResult($resultResource, Collection $collection = null)
    {
        if ($collection === null) { // update or insert
            if ($this->queryType === Query::TYPE_INSERT) {
                $resultResource = pg_query($this->connection, 'SELECT lastval()');
                $row = pg_fetch_row($resultResource);
                return $row[0];
            }

            return pg_affected_rows($resultResource);
        }

        if ($resultResource === false) {
            throw new QueryException(pg_result_error($this->connection));
        }

        $result = new Result($resultResource);
        $result->setQuery($this->query);

        $collection->set($result);
        return true;
    }

    /**
     * @throws \fastorm\Driver\Exception
     */
    public function close()
    {
        if ($this->statementName === null) {
            throw new Exception('statement->close can\'t be called before statement->execute');
        }

        pg_query($this->connection, 'DEALLOCATE "' . $this->statementName . '"');
    }
}
