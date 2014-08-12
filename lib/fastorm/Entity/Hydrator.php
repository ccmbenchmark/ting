<?php

namespace fastorm\Entity;

use fastorm\Entity\Metadata;
use fastorm\Entity\MetadataRepository;

class Hydrator
{

    protected $metadataRepository = array();

    public function __construct(MetadataRepository $metadataRepository = null)
    {
        if ($metadataRepository === null) {
                $metadataRepository = MetadataRepository::getInstance();
        }

        $this->metadataRepository = $metadataRepository;
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
                        $result[$column['table']]       = $metadata->createObject();
                    },
                    function () use (&$result, $column) {
                        $result[$column['table']] = new \stdClass();
                    }
                );
            }

            if (isset($metadataList[$column['table']]) === true
                && $metadataList[$column['table']]->hasColumn($column['orgName'])
            ) {
                $metadataList[$column['table']]->setObjectProperty(
                    $result[$column['table']],
                    $column['orgName'],
                    $column['value']
                );
            } else {
                $property = 'db__' . $column['name'];
                $result[$column['table']]->$property = $column['value'];
            }
        }

        return $result;
    }
}
