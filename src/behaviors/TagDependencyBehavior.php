<?php
/**
 * Created by PhpStorm
 * User: elfuvo
 * Date: 2021-05-17
 * Time: 14:08
 */

namespace elfuvo\yiiDocumentStore\behaviors;

use elfuvo\yiiDocumentStore\collection\YiiCollectionInterface;
use yii\base\Behavior;
use yii\base\Event;
use yii\base\InvalidConfigException;
use yii\caching\CacheInterface;
use yii\caching\TagDependency;
use yii\di\Instance;

/**
 * Class TagDependencyBehavior
 *
 * ```php
 * public function behaviors()
 * {
 *     return [
 *         'TagDependencyBehavior' => TagDependencyBehavior::class,
 *     ];
 * }
 * ```
 *
 * use
 *
 * ```php
 * $dependency = new TagDependency([
 *      'tags' => [
 *          Entity::class,
 *      ],
 * ]);
 * ```
 *
 * @package elfuvo\yiiDocumentStore\behaviors
 */
class TagDependencyBehavior extends Behavior
{
    /**
     * @var string|CacheInterface
     */
    public $cache = 'cache';

    /**
     * @return array
     */
    public function events()
    {
        return [
            YiiCollectionInterface::EVENT_AFTER_INSERT => 'invalidate',
            YiiCollectionInterface::EVENT_AFTER_UPDATE => 'invalidate',
            YiiCollectionInterface::EVENT_AFTER_DELETE => 'invalidate',
        ];
    }

    /**
     * @param Event $event
     *
     * @throws InvalidConfigException
     */
    public function invalidate(Event $event)
    {
        /** @var \elfuvo\documentStore\collection\CollectionInterface $sender */
        $sender = $event->sender;

        /** @var CacheInterface $cache */
        $cache = Instance::ensure($this->cache, CacheInterface::class);

        $tags = [
            get_class($sender),
        ];

        TagDependency::invalidate($cache, $tags);
    }
}
