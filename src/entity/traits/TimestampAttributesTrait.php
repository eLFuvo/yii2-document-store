<?php
/**
 * Created by PhpStorm
 * User: elfuvo
 * Date: 2021-04-30
 * Time: 11:01
 */

namespace elfuvo\yiiDocumentStore\entity\traits;

/**
 * Class TimestampAttributesTrait
 * @package elfuvo\yiiDocumentStore\entity\traits
 */
trait TimestampAttributesTrait
{
    /**
     * @var int
     */
    public int $createdAt = 0;

    /**
     * @var int
     */
    public int $updatedAt = 0;

    /**
     * @return array
     */
    public function rules(): array
    {
        return [
            [['createdAt', 'updatedAt'], 'integer']
        ];
    }
}
