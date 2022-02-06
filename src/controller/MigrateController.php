<?php
/**
 * Created by PhpStorm
 * User: elfuvo
 * Date: 2021-04-28
 * Time: 10:43
 */

namespace elfuvo\yiiDocumentStore\controller;

use Yii;
use yii\console\controllers\BaseMigrateController;

/**
 * Class MigrateController
 * @package elfuvo\yiiDocumentStore\controller
 */
class MigrateController extends \yii\console\controllers\MigrateController
{
    /**
     * @var string
     */
    public string $collection = '';

    /**
     * {@inheritdoc}
     */
    public $templateFile = __DIR__ . '/../migration/views/migration.php';

    /**
     * {@inheritdoc}
     */
    public function options($actionID): array
    {
        return array_merge(
            BaseMigrateController::options($actionID),
            ['migrationTable'], // global for all actions
            $actionID === 'create'
                ? ['templateFile', 'collection']
                : []
        );
    }

    /**
     * @param array $params
     * @return string
     */
    protected function generateMigrationSourceCode($params): string
    {
        return $this->renderFile(
            Yii::getAlias($this->templateFile),
            array_merge($params, [
                'collection' => $this->collection,
            ])
        );
    }
}
