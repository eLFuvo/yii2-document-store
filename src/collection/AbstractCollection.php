<?php
/**
 * Created by PhpStorm
 * User: elfuvo
 * Date: 2021-04-29
 * Time: 12:47
 */

namespace elfuvo\yiiDocumentStore\collection;

use elfuvo\documentStore\entity\EntityInterface;
use elfuvo\documentStore\Exception;
use elfuvo\documentStore\repository\RepositoryInterface;
use elfuvo\yiiDocumentStore\entity\AbstractEntity;
use elfuvo\yiiDocumentStore\traits\ObjectEventsTrait;
use yii\base\Model;
use yii\base\ModelEvent;
use yii\db\AfterSaveEvent;

/**
 * Class AbstractCollection
 * @package elfuvo\yiiDocumentStore\collection
 *
 * @property-read \elfuvo\documentStore\entity\EntityInterface $entity
 */
abstract class AbstractCollection extends \elfuvo\documentStore\collection\AbstractCollection implements
    YiiCollectionInterface
{
    use ObjectEventsTrait;

    /**
     * @inheritDoc
     * @return \elfuvo\yiiDocumentStore\repository\BaseRepository|RepositoryInterface
     */
    abstract public function getRepository(): RepositoryInterface;

    /**
     * @inheritDoc
     */
    public function getEntity(): AbstractEntity
    {
        return new $this->entityClass();
    }

    /**
     * @param \elfuvo\documentStore\entity\EntityInterface $entity
     * @return bool
     * @throws \elfuvo\documentStore\Exception
     * @throws \yii\base\InvalidConfigException
     */
    public function save(EntityInterface $entity): bool
    {
        $insert = is_null($entity->getId());
        if ($this->beforeSave($insert, $entity)) {
            $before = $insert ? [] : $entity->extract();

            $result = $this->getRepository()->save($entity);

            if ($result->getWarningsCount() === 0) {
                if (!$entity->getId() && $ids = $result->getGeneratedIds()) {
                    $entity->setId(array_shift($ids));
                }
                $this->afterSave($insert, $before, $entity);

                return true;
            } else {
                throw new Exception(implode(PHP_EOL, $result->getWarnings()));
            }
        }

        return false;
    }

    /**
     * @param \elfuvo\documentStore\entity\EntityInterface|Model $entity
     * @return bool
     * @throws \elfuvo\documentStore\Exception
     * @throws \yii\base\InvalidConfigException
     */
    public function delete(EntityInterface $entity): bool
    {
        if ($this->beforeDelete($entity)) {
            $result = $this->getRepository()->delete($entity->getId());

            if ($result->getWarningsCount() === 0) {
                $this->afterDelete($entity);

                return true;
            }
        }

        return false;
    }

    /**
     * @param bool $insert
     * @param \elfuvo\documentStore\entity\EntityInterface $entity
     * @return bool
     * @throws \yii\base\InvalidConfigException
     */
    protected function beforeSave(bool $insert, EntityInterface $entity): bool
    {
        $this->ensureBehaviors();
        $event = new ModelEvent([
            'sender' => $entity,
        ]);
        $this->trigger(
            $insert ? YiiCollectionInterface::EVENT_BEFORE_INSERT : YiiCollectionInterface::EVENT_BEFORE_UPDATE,
            $event
        );

        return $event->isValid;
    }

    /**
     * @param bool $insert
     * @param array $before
     * @param EntityInterface $entity
     * @throws \yii\base\InvalidConfigException
     */
    protected function afterSave(bool $insert, array $before, EntityInterface $entity)
    {
        $this->ensureBehaviors();
        if ($insert) {
            $this->trigger(
                YiiCollectionInterface::EVENT_AFTER_INSERT,
                new ModelEvent([
                    'sender' => $entity,
                ])
            );
        } else {
            $changedAttributes = [];
            foreach ($entity->extract() as $name => $value) {
                if (!isset($before[$name]) || isset($before[$name]) && $before[$name] != $value) {
                    $changedAttributes[$name] = $value;
                }
            }
            $this->trigger(
                YiiCollectionInterface::EVENT_AFTER_UPDATE,
                new AfterSaveEvent([
                    'sender' => $entity,
                    'changedAttributes' => $changedAttributes,
                ])
            );
        }
    }

    /**
     * @param \elfuvo\documentStore\entity\EntityInterface $entity
     * @return bool
     * @throws \yii\base\InvalidConfigException
     */
    protected function beforeDelete(EntityInterface $entity): bool
    {
        $this->ensureBehaviors();
        $event = new ModelEvent([
            'sender' => $entity,
        ]);
        $this->trigger(YiiCollectionInterface::EVENT_BEFORE_DELETE, $event);

        return $event->isValid;
    }

    /**
     * @param \elfuvo\documentStore\entity\EntityInterface $entity
     * @throws \yii\base\InvalidConfigException
     */
    protected function afterDelete(EntityInterface $entity)
    {
        $this->ensureBehaviors();
        $this->trigger(YiiCollectionInterface::EVENT_AFTER_DELETE, new ModelEvent([
            'sender' => $entity,
        ]));
    }

    /**
     * turn on caching selected documents
     */
    public function cache()
    {
        $this->cache = true;
    }

    /**
     * turn off caching selected documents
     */
    public function noCache()
    {
        $this->cache = false;
        $this->items = [];
    }
}
