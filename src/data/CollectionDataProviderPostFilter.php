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
use yii\helpers\ArrayHelper;

/**
 * Class CollectionDataProviderPostFilter
 * @package elfuvo\yiiDocumentStore\data
 */
class CollectionDataProviderPostFilter extends BaseDataProvider
{
    /**
     * @var CacheInterface|string|bool
     */
    public $cache = false;

    /**
     * cache duration in seconds
     *
     * @var int
     */
    public int $cacheDuration = 3600;

    /**
     * @var \yii\caching\Dependency|null
     */
    public ?Dependency $cacheDependency = null;

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
     * @var string
     */
    public string $postFilterName = 'enchantedFilter';

    /**
     * this is a limit for collection query
     *
     * @var int
     */
    public int $defaultLimit = 100;

    /**
     * unset attributes of collection item before caching (not need for list)
     *
     * @var array|null
     */
    public ?array $unsetAttributes = null;

    /**
     * @var BaseRepository|null
     */
    protected ?BaseRepository $repository = null;

    /**
     * @var array
     */
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
        if ($this->cache) {
            $this->cache = Instance::ensure($this->cache, CacheInterface::class);
        }
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
        $this->repository->limit($this->defaultLimit);

        if ($this->cache) {
            $this->collection->cache();
            $key = $this->repository->getRawSql();
            if (($entityList = $this->cache->get($key)) === false) {
                if ($this->unsetAttributes) {// remove unused data from cacheable item
                    $unsetAttributes = $this->unsetAttributes;
                    $entityList = array_map(
                        static function (array $item) use ($unsetAttributes) {
                            foreach ($unsetAttributes as $attribute) {
                                ArrayHelper::remove($item, $attribute);
                            }
                            return $item;
                        },
                        $this->repository->all(false)
                    );
                } else {
                    $entityList = $this->repository->all(false);
                }

                $this->cache->set($key, $entityList, $this->cacheDuration, $this->cacheDependency);
            }
        } else {
            $entityList = $this->repository->all(false);
        }
        foreach ($entityList as $data) {
            $this->collection->add($this->collection->getEntity()->populate($data));
        }
        unset($entityList, $entity, $element);

        if ($this->collection::hasMacro($this->postFilterName)) {
            $this->collection->{$this->postFilterName}();
        }

        if ($this->getSort()) {
            $orders = [];
            foreach ($this->getSort()->getOrders() as $attribute => $order) {
                if ($this->collection->getEntity()->getAttributes([$attribute])) {
                    $orders[] = [
                        $attribute,
                        $order == SORT_ASC ? 'asc' : 'desc',
                    ];
                }
            }
            if ($orders) {
                $this->collection = $this->collection->sortBy($orders);
            }
        }

        if (($pagination = $this->getPagination()) !== false) {
            $this->_totalCount = $pagination->totalCount = $this->collection->count();
            if ($pagination->totalCount === 0) {
                return [];
            }
            $this->_models = $this->collection->splice($this->pagination->getOffset(),
                $this->pagination->getLimit())->all();
        } else {
            $this->_totalCount = $this->collection->count();
            $this->_models = $this->collection->all();
        }
        $this->collection->noCache();

        return $this->_models;
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
     * @return int
     * @throws \elfuvo\documentStore\Exception
     */
    protected function prepareTotalCount(): int
    {
        $this->prepareModels();

        return (int)$this->_totalCount;
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
        if ($this->getPagination() === false) {
            return $this->getCount();
        } elseif ($this->_totalCount === null) {
            $this->prepareTotalCount();
        }

        return $this->_totalCount;
    }
}
