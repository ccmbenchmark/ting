<?php

namespace fastorm\Entity;

use fastorm\NotifyPropertyInterface;
use fastorm\PropertyListenerInterface;
use fastorm\UnitOfWork;
use fastorm\Entity\Metadata;
use fastorm\Entity\MetadataRepository;

class Hydrator
{

    protected $metadataRepository = array();

    public function __construct(MetadataRepository $metadataRepository = null, UnitofWork $unitOfWork = null)
    {
        if ($metadataRepository === null) {
            $metadataRepository = MetadataRepository::getInstance();
        }

        $this->metadataRepository = $metadataRepository;

        if ($unitOfWork === null) {
            $unitOfWork = UnitOfWork::getInstance();
        }

        $this->unitOfWork = $unitOfWork;
    }

    public function hydrate($columns = array())
    {
        $result       = array();
        $metadataList = array();
        foreach ($columns as $column) {
            if ($column['table'] === '') {
                $column['table'] = 'db__table';
            }

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
                $property = 'db__' . $column['name'];
                $result[$column['table']]->$property = $column['value'];
            }
        }

        foreach ($result as $entity) {
            $this->unitOfWork->manage($entity);
        }

        return $result;
    }
}
