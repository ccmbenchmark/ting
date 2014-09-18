<?php

namespace CCMBenchmark\Ting\Driver\Pgsql;

use CCMBenchmark\Ting\Driver\Exception;
use CCMBenchmark\Ting\Driver\QueryException;
use CCMBenchmark\Ting\Driver\StatementInterface;
use CCMBenchmark\Ting\Entity\Collection;
use CCMBenchmark\Ting\Query\QueryAbstract;

class Statement implements StatementInterface
{

    protected $connection    = null;
    protected $statementName = null;
    protected $queryType     = null;
    protected $query         = null;


    /**
     * @param $connection
     * @return $this
     */
    public function setConnection($connection)
    {
        $this->connection = $connection;

        return $this;
    }

    /**
     * @param $query
     * @return $this
     */
    public function setQuery($query)
    {
        $this->query = (string) $query;

        return $this;
    }

    /**
     * @param $type
     * @return $this
     * @throws \CCMBenchmark\Ting\Driver\Exception
     */
    public function setQueryType($type)
    {
        if (
            in_array(
                $type,
                array(QueryAbstract::TYPE_RESULT, QueryAbstract::TYPE_AFFECTED, QueryAbstract::TYPE_INSERT)
            ) === false
        ) {
            throw new Exception('setQueryType should use one of constant Statement::TYPE_*');
        }

        $this->queryType = $type;

        return $this;
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
     * @throws \CCMBenchmark\Ting\Driver\QueryException
     */
    public function setCollectionWithResult($resultResource, Collection $collection = null)
    {
        if ($collection === null) { // update or insert
            if ($this->queryType === QueryAbstract::TYPE_INSERT) {
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
     * @throws \CCMBenchmark\Ting\Driver\Exception
     */
    public function close()
    {
        if ($this->statementName === null) {
            throw new Exception('statement->close can\'t be called before statement->execute');
        }

        pg_query($this->connection, 'DEALLOCATE "' . $this->statementName . '"');
    }
}
