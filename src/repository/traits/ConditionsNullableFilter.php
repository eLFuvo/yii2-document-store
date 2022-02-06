<?php
/**
 * Created by PhpStorm
 * User: elfuvo
 * Date: 2021-04-29
 * Time: 16:23
 */

namespace elfuvo\yiiDocumentStore\repository\traits;

/**
 * Trait ConditionNullableFilter
 * @package elfuvo\yiiDocumentStore\repository\traits
 */
trait ConditionsNullableFilter
{
    /**
     * @param string $condition
     * @param string $column
     * @param $value
     * @return $this
     * @throws \elfuvo\documentStore\Exception
     */
    public function andFilterWhere(string $condition, string $column, $value): self
    {
        if (is_null($value)) {
            return $this;
        }
        if (trim($value, '% ') > '') {
            $this->andWhere($condition, $column, $value);
        }

        return $this;
    }

    /**
     * @param string $condition
     * @param string $column
     * @param $value
     * @return $this
     * @throws \elfuvo\documentStore\Exception
     */
    public function orFilterWhere(string $condition, string $column, $value): self
    {
        if (is_null($value)) {
            return $this;
        }
        if (trim($value, '% ') > '') {
            $this->orWhere($condition, $column, $value);
        }

        return $this;
    }
}
