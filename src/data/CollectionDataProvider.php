<?php
/**
 * Created by PhpStorm
 * User: elfuvo
 * Date: 2021-06-04
 * Time: 13:35
 */

namespace elfuvo\yiiDocumentStore\data;

use elfuvo\documentStore\entity\EntityInterface;
use elfuvo\yiiDocumentStore\collection\AbstractCollection;
use elfuvo\yiiDocumentStore\repository\BaseRepository;
use yii\base\InvalidConfigException;
use yii\caching\CacheInterface;
use yii\caching\Dependency;
use yii\data\BaseDataProvider;
use yii\di\Instance;

/**
 * Class CollectionDataProvider
 * @package elfuvo\yiiDocumentStore\data
 */
class CollectionDataProvider extends BaseDataProvider
{
    /**
     * @var string|callable the column that is used as the key of the data models.
     * This can be either a column name, or a callable that returns the key value of a given data model.
     * If this is not set, the "_id" column of collection will be used.
     * @see EntityInterface::getId()
     * @see getKeys()
     */
    public $key;

    /**
     * @var AbstractCollection|null
     */
    public ?AbstractCollection $collection = null;

    /**
     * @var int
     */
    public int $defaultLimit = 500;

    /**
     * @var BaseRepository|null
     */
    protected ?BaseRepository $repository = null;

    private array $_models = [];

    /**
     * @var int|null
     */
    private ?int $_totalCount = null;

    /**
     * @throws \yii\base\InvalidConfigException
     */
    public function init()
    {
        parent::init();
        if (!$this->collection instanceof AbstractCollection) {
            throw new InvalidConfigException('Property "collection" should be instance of ' . AbstractCollection::class);
        }
        if (!$this->collection->getRepository() instanceof BaseRepository) {
            throw new InvalidConfigException('Collection repository should be instance of ' . BaseRepository::class);
        }
        $this->repository = $this->collection->getRepository();
    }

    /**
     * @return array
     * @throws \elfuvo\documentStore\Exception
     */
    public function prepareModels(): array
    {
        if ($this->_models) {
            return $this->_models;
        }
        $this->collection->noCache();

        if (($pagination = $this->getPagination()) !== false) {
            $pagination->totalCount = $this->getTotalCount();
            if ($pagination->totalCount === 0) {
                return [];
            }
            $this->repository->limit($pagination->getLimit())->offset($pagination->getOffset());
        } else {
            $this->repository->limit($this->defaultLimit);
        }
        if ($this->getSort()) {
            $orders = [];
            foreach ($this->getSort()->getOrders() as $attribute => $order) {
                $orders[] = [$attribute => $order === SORT_ASC ? 'asc' : 'desc'];
            }
            $this->repository->sort($orders);
            unset($orders);
        }

        $this->_models = $this->repository->all();

        return $this->_models;
    }

    /**
     * @return int
     * @throws \elfuvo\documentStore\Exception
     */
    protected function prepareTotalCount(): int
    {
        $repository = clone $this->repository;

        return $repository->limit(0)->count();
    }

    /**
     * Returns the data models in the current page.
     * @return array the list of data models in the current page.
     */
    public function getModels(): array
    {
        $this->prepare();

        return $this->_models;
    }

    /**
     * Sets the data models in the current page.
     * @param array $models the models in the current page
     */
    public function setModels($models)
    {
        $this->_models = $models;
    }

    /**
     * @param array $models
     * @return array|int[]|string[]
     * @throws \Exception
     */
    protected function prepareKeys($models): array
    {
        $keys = [];
        if ($this->key !== null) {
            foreach ($models as $model) {
                if (is_string($this->key)) {
                    $keys[] = $model[$this->key];
                } else {
                    $keys[] = call_user_func($this->key, $model);
                }
            }
        } elseif ($models) {
            $keys = array_map(static function (EntityInterface $model) {
                return $model->getId();
            }, $models);
            // if "_id" is not provided use simple keys
            $keys = array_filter($keys);
            if (empty($keys)) {
                $keys = array_keys($models);
            }
        }

        return $keys;
    }

    /**
     * Returns the total number of data models.
     * When [[pagination]] is false, this returns the same value as [[count]].
     * Otherwise, it will call [[prepareTotalCount()]] to get the count.
     * @return int total number of possible data models.
     * @throws \elfuvo\documentStore\Exception
     */
    public function getTotalCount(): ?int
    {
        if ($this->_totalCount === null) {
            $this->_totalCount = $this->prepareTotalCount();
        }

        return $this->_totalCount;
    }
}
