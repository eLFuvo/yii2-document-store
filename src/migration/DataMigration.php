<?php
/**
 * Created by PhpStorm
 * User: elfuvo
 * Date: 2021-04-25
 * Time: 17:47
 */

namespace elfuvo\yiiDocumentStore\migration;

use elfuvo\documentStore\collection\CollectionInterface;
use elfuvo\documentStore\entity\EntityInterface;
use elfuvo\documentStore\Exception;
use elfuvo\documentStore\Expression;
use yii\helpers\ArrayHelper;

/**
 * Trait DataMigration
 * @package elfuvo\yiiDocumentStore\migration
 */
trait DataMigration
{
    /**
     * @param string $collection
     * @param array $document
     * @throws \mysql_xdevapi\Exception
     */
    public function insert(string $collection, array $document)
    {
        $time = $this->beginCommand("insert into $collection");
        $collection = $this->checkCollection($collection);
        $collection->add($document);
        $this->endCommand($time);
    }

    /**
     * @param string $collection - collection name
     * @param array $documents - array of documents
     * @throws \mysql_xdevapi\Exception
     */
    public function batchInsert(string $collection, array $documents)
    {
        $time = $this->beginCommand("insert into $collection");
        $collection = $this->checkCollection($collection);
        foreach ($documents as $document) {
            $collection->add($document);
        }
        $this->endCommand($time);
    }

    /**
     * @param string $collection
     * @param \elfuvo\documentStore\entity\EntityInterface $document
     */
    public function update(string $collection, EntityInterface $document)
    {
        $time = $this->beginCommand("update $collection");
        $collection = $this->checkCollection($collection);
        $collection->addOrReplaceOne($document->getId(), json_encode($document->extract()));
        $this->endCommand($time);
    }

    /**
     * @param \elfuvo\documentStore\collection\CollectionInterface|\elfuvo\documentStore\repository\AbstractRepository $collection
     * @param array $conditions - search conditions, for example: [['=', 'active', true], new Expression('createdAt < \'2021-04-01\'')]
     * @param string[]|int[]|null[] $data - data for update. for example: ['title' => 'foo', 'description' => 'bar']
     * @throws \elfuvo\documentStore\Exception
     * @throws \mysql_xdevapi\Exception
     * @throws \Exception
     */
    public function updateAll(CollectionInterface $collection, array $conditions = [], array $data = [])
    {
        $time = $this->beginCommand("update all " . $collection::collectionName());
        $this->checkCollection($collection::collectionName());
        /** @var \elfuvo\documentStore\repository\AbstractRepository $repository */
        $repository = $collection->getRepository();
        foreach ($conditions as $condition) {
            if (is_array($condition)) {
                if (count($condition) !== 3) {
                    throw new Exception('"where" expression must contains condition, column name and value');
                }
                $repository->andWhere($condition[0], $condition[1], $condition[2]);
            } elseif ($condition instanceof Expression) {
                $repository->andExprWhere($condition);
            }
        }
        $documents = $repository->all(false);
        foreach ($documents as $document) {
            $id = ArrayHelper::getValue($document, '_id');
            if ($id) {
                $modify = $repository->getCollection()->modify('_id=\'' . $id . '\'');
                foreach ($data as $property => $value) {
                    $modify->set($property, $value);
                }
                $modify->execute();
            }
        }

        $this->endCommand($time);
    }

    /**
     * @param string $collection
     * @param \elfuvo\documentStore\entity\EntityInterface $document
     * @throws \mysql_xdevapi\Exception
     */
    public function delete(string $collection, EntityInterface $document)
    {
        $time = $this->beginCommand("delete from $collection");
        $collection = $this->checkCollection($collection);
        $collection->removeOne($document->getId());
        $this->endCommand($time);
    }

    /**
     * @param \elfuvo\documentStore\collection\CollectionInterface|\elfuvo\documentStore\repository\AbstractRepository $collection
     * @param array $conditions - search conditions, for example: [['=', 'active', true], new Expression('createdAt < \'2021-04-01\'')]
     * @throws \elfuvo\documentStore\Exception
     * @throws \mysql_xdevapi\Exception
     */
    public function deleteAll(CollectionInterface $collection, array $conditions = [])
    {
        $time = $this->beginCommand("delete from " . $collection::collectionName());
        $this->checkCollection($collection::collectionName());
        /** @var \elfuvo\documentStore\repository\AbstractRepository $repository */
        $repository = $collection->getRepository();
        foreach ($conditions as $condition) {
            if (is_array($condition)) {
                if (count($condition) !== 3) {
                    throw new Exception('"where" expression must contains condition, column name and value');
                }
                $repository->andWhere($condition[0], $condition[1], $condition[2]);
            } elseif ($condition instanceof Expression) {
                $repository->andExprWhere($condition);
            }
        }
        $repository->getCollection()->remove($repository->getWhere())->execute();

        $this->endCommand($time);
    }
}
