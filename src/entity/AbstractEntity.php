<?php
/**
 * Created by PhpStorm
 * User: elfuvo
 * Date: 2021-04-20
 * Time: 12:54
 */

namespace elfuvo\yiiDocumentStore\entity;

use elfuvo\documentStore\entity\EntityInterface;
use Illuminate\Contracts\Support\Arrayable;
use JsonSerializable;
use yii\base\Model;

/**
 * Class AbstractEntity
 * @package elfuvo\documentStore\entity
 *
 * @property-read null|string $id
 * @property int $createdAt
 * @property int $updatedAt
 */
abstract class AbstractEntity extends Model implements EntityInterface, JsonSerializable, Arrayable
{
    /**
     * @var string|int|null
     */
    protected $_id = null;

    /**
     * @inheritDoc
     */
    public function getId()
    {
        return $this->_id ?: null;
    }

    /**
     * @param string|int $id
     */
    public function setId($id): void
    {
        $this->_id = $id;
    }

    /**
     * @inheritDoc
     */
    public function populate(array $data): EntityInterface
    {
        if (isset($data['_id'])) {
            $this->setId((string)$data['_id']);
            unset($data['_id']);
        }
        $this->setAttributes($data);

        return $this;
    }

    /**
     * @return array|null
     */
    public function extract(): ?array
    {
        return $this->toArray();
    }

    /**
     * @return array|null
     */
    public function jsonSerialize(): ?array
    {
        return $this->extract();
    }
}
