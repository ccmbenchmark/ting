<?php

namespace fastorm\Entity;

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
        $result = array();
        foreach ($columns as $column) {
            if ($column['table'] === '') {
                $column['table'] = 'db__table';
            }

            if (isset($result[$column['table']]) === false) {
                $this->metadataRepository->hasMetadataForTable(
                    $column['orgTable'],
                    function ($metadata) use (&$result, $column) {
                        $result[$column['table']] = $metadata->createObject();
                    },
                    function () use (&$result, $column) {
                        $result[$column['table']] = new \stdClass();
                    }
                );
            }

            $this->metadataRepository->hasMetadataForTable(
                $column['orgTable'],
                function ($metadata) use (&$result, $column) {
                    $metadata->setObjectProperty($result[$column['table']], $column['orgName'], $column['value']);
                },
                function () use ($result, $column) {
                    $property = 'db__' . $column['name'];
                    $result[$column['table']]->$property = $column['value'];
                }
            );
        }

        return $result;
    }
}
