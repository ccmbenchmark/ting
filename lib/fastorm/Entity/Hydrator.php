<?php

namespace fastorm\Entity;

use fastorm\ContainerInterface;
use fastorm\NotifyPropertyInterface;
use fastorm\PropertyListenerInterface;
use fastorm\UnitOfWork;
use fastorm\Entity\Metadata;
use fastorm\Entity\MetadataRepository;

class Hydrator
{

    protected $metadataRepository = null;
    protected $unitOfWork         = null;

    public function __construct(MetadataRepository $metadaRepository, UnitOfWork $unitOfWork)
    {
        $this->metadataRepository = $metadaRepository;
        $this->unitOfWork         = $unitOfWork;
    }

    public function hydrate($columns = array())
    {
        $result       = array();
        $metadataList = array();
        foreach ($columns as $column) {
            if (isset($result[$column['table']]) === false) {
                $this->metadataRepository->findMetadataForTable(
                    $column['orgTable'],
                    function (Metadata $metadata) use ($column, &$result, &$metadataList) {
                        $metadataList[$column['table']] = $metadata;
                        $result[$column['table']]       = $metadata->createEntity();
                    },
                    function () use (&$result, $column) {
                        $result[$column['table']] = new \stdClass();
                    }
                );
            }

            if (isset($metadataList[$column['table']]) === true
                && $metadataList[$column['table']]->hasColumn($column['orgName'])
            ) {
                $metadataList[$column['table']]->setEntityProperty(
                    $result[$column['table']],
                    $column['orgName'],
                    $column['value']
                );
            } else {
                if (isset($result['db__table']) === false) {
                    $result['db__table'] = new \stdClass();
                }

                $result['db__table']->$column['name'] = $column['value'];
            }
        }

        foreach ($result as $entity) {
            $this->unitOfWork->manage($entity);
        }

        return $result;
    }
}
