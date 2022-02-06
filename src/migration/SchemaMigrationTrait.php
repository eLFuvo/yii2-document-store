<?php
/**
 * Created by PhpStorm
 * User: elfuvo
 * Date: 2021-05-04
 * Time: 18:01
 */

namespace elfuvo\yiiDocumentStore\migration;

use elfuvo\documentStore\Exception;
use elfuvo\documentStore\migration\Index;
use mysql_xdevapi\Collection;

/**
 * Class SchemaMigrationTrait
 * @package elfuvo\yiiDocumentStore\migration
 */
trait SchemaMigrationTrait
{
    /**
     * @inheritDoc
     */
    public function createCollection(string $collection): Collection
    {
        $time = $this->beginCommand('Create collection ' . $collection);
        $collection = $this->getSchema()->createCollection($collection);
        $this->endCommand($time);

        return $collection;
    }

    /**
     * @inheritDoc
     */
    public function dropCollection(string $collection): bool
    {
        $time = $this->beginCommand('Drop collection ' . $collection);
        $result = $this->getSchema()->dropCollection($collection);
        $this->endCommand($time);

        return $result;
    }

    /**
     * @inheritDoc
     */
    public function addProperty(string $collection, string $property, $defaultValue = null): bool
    {
        $time = $this->beginCommand('Add new property ' . $property . ' to ' . $collection);
        $result = $this->getSchema()->getCollection($collection)
            ->modify('true')
            ->patch(json_encode([$property => $defaultValue]))
            ->execute();
        $this->endCommand($time);

        return $result->getWarningsCount() === 0;
    }

    /**
     * @inheritDoc
     */
    public function dropProperty(string $collection, string $property): bool
    {
        $time = $this->beginCommand('Drop property ' . $property . ' from ' . $collection);
        $result = $this->getSchema()->getCollection($collection)
            ->modify('true')
            ->unset([$property])
            ->execute();
        $this->endCommand($time);

        return $result->getWarningsCount() === 0;
    }

    /**
     * @inheritDoc
     */
    public function createIndex(string $collection, Index $index): bool
    {
        $time = $this->beginCommand('Create new index ' . $index->name . ' for ' . $collection);
        $collectionObj = $this->getSchema()->getCollection($collection);
        if (!$collectionObj) {
            $this->endCommand($time);
            throw new Exception('Collection "' . $collection . '" does not exists.');
        }
        if ($index->isValid()) {
            $collectionObj->createIndex(
                $index->name,
                json_encode($index)
            );
            $this->endCommand($time);

            return true;
        } else {
            $this->endCommand($time);
            if ($index->isArray()) {
                throw new Exception('Index config for "' . $index->name . '" is invalid. Supported types for array field: UNSIGNED, CHAR(n).');
            }
            throw new Exception('Index config for "' . $index->name . '" is invalid.');
        }
    }

    /**
     * @inheritDoc
     */
    public function dropIndex(string $collection, string $name): bool
    {
        $time = $this->beginCommand('Drop index ' . $name . ' from ' . $collection);
        $result = $this->getSchema()->getCollection($collection)->dropIndex($name);
        $this->endCommand($time);

        return $result;
    }
}
