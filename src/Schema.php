<?php
/**
 * Created by PhpStorm
 * User: elfuvo
 * Date: 2021-04-27
 * Time: 14:16
 */

namespace elfuvo\yiiDocumentStore;

use yii\base\BaseObject;

/**
 * @link http://json-schema.org/draft-06/schema#
 *
 * Class Schema
 * @package elfuvo\yiiDocumentStore
 */
class Schema  extends BaseObject
{
    const TYPE_CHAR = 'char';
    const TYPE_STRING = 'string';
    const TYPE_TEXT = 'text';
    const TYPE_INTEGER = 'integer';
    const TYPE_FLOAT = 'number';
    const TYPE_NULL = 'null';
    const TYPE_OBJECT = 'object';
    const TYPE_DATE = 'date';
    const TYPE_BOOLEAN = 'boolean';
}
