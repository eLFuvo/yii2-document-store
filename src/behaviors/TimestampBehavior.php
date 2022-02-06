<?php
/**
 * Created by PhpStorm
 * User: elfuvo
 * Date: 2021-04-29
 * Time: 12:40
 */

namespace elfuvo\yiiDocumentStore\behaviors;

use Closure;
use elfuvo\documentStore\entity\EntityInterface;
use elfuvo\yiiDocumentStore\collection\YiiCollectionInterface;
use yii\base\Behavior;
use yii\base\Event;
use yii\base\Model;

/**
 * Class TimestampBehavior
 * @package elfuvo\yiiDocumentStore\behaviors
 */
class TimestampBehavior extends Behavior
{
    /**
     * @var string the attribute that will receive timestamp value
     * Set this property to false if you do not want to record the creation time.
     */
    public string $createdAtAttribute = 'createdAt';
    /**
     * @var string the attribute that will receive timestamp value.
     * Set this property to false if you do not want to record the update time.
     */
    public string $updatedAtAttribute = 'updatedAt';

    /**
     * @var int|null|callable
     *
     * In case, when the value is `null`, the result of the PHP function [time()](https://secure.php.net/manual/en/function.time.php)
     * will be used as value.
     */
    public ?int $value = null;

    /**
     * @var YiiCollectionInterface|\elfuvo\documentStore\collection\CollectionInterface|\elfuvo\yiiDocumentStore\collection\AbstractCollection
     */
    public $owner;

    /**
     * @return string[]
     */
    public function events(): array
    {
        return [
            YiiCollectionInterface::EVENT_BEFORE_INSERT => 'setTimestamp',
            YiiCollectionInterface::EVENT_BEFORE_UPDATE => 'setTimestamp',
        ];
    }

    /**
     * @internal
     */
    public function setTimestamp(Event $event)
    {
        if ($event->sender instanceof Model && $event->sender instanceof EntityInterface) {
            if (!$event->sender->getId() && $event->sender->canSetProperty($this->createdAtAttribute)) {
                $event->sender->{$this->createdAtAttribute} = $this->getValue();
            }
            if ($event->sender->canSetProperty($this->updatedAtAttribute)) {
                $event->sender->{$this->updatedAtAttribute} = $this->getValue();
            }
        }
    }

    /**
     * get current timestamp value
     *
     * @return int|null
     */
    protected function getValue(): ?int
    {
        if ($this->value instanceof Closure || (is_array($this->value) && is_callable($this->value))) {
            return call_user_func($this->value);
        }

        return $this->value ?: time();
    }
}
