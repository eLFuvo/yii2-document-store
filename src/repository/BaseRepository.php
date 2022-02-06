<?php
/**
 * Created by PhpStorm
 * User: elfuvo
 * Date: 2021-04-25
 * Time: 12:21
 */

namespace elfuvo\yiiDocumentStore\repository;

use elfuvo\documentStore\connection\ConnectionInterface;
use elfuvo\documentStore\repository\AbstractRepository;
use elfuvo\yiiDocumentStore\repository\traits\ConditionsNullableFilter;
use yii\base\Exception;
use yii\di\Instance;

/**
 * Class BaseRepository
 * @package elfuvo\yiiDocumentStore\repository
 */
class BaseRepository extends AbstractRepository
{
    use ConditionsNullableFilter;

    /**
     * @return \elfuvo\documentStore\connection\ConnectionInterface
     * @throws \yii\base\InvalidConfigException
     */
    public static function getDb(): ConnectionInterface
    {
        if (!static::$db) {
            static::$db = Instance::ensure(ConnectionInterface::class, ConnectionInterface::class);
        }

        return static::$db;
    }

    /**
     * @param \elfuvo\documentStore\connection\ConnectionInterface $db
     * @throws \yii\base\Exception
     */
    public static function setDb(ConnectionInterface $db): void
    {
        throw new Exception('Method is not allowed');
    }

    /**
     * @param string $id
     * @return \elfuvo\documentStore\repository\AbstractRepository|\elfuvo\yiiDocumentStore\repository\BaseRepository
     * @throws \elfuvo\documentStore\Exception
     */
    public function byId(string $id)
    {
        return $this->andWhere('=', '_id', $id);
    }
}
