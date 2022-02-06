<?php
/**
 * This view is used by console/controllers/MigrateController.php.
 *
 * The following variables are available in this view:
 */
/* @var $className string the new migration class name without namespace */
/* @var $namespace string the new migration class namespace */
/* @var $collection string collection name */

echo "<?php\n";
if (!empty($namespace)) {
    echo "\nnamespace {$namespace};\n";
}
?>

use elfuvo\yiiDocumentStore\migration\Migration;
use elfuvo\documentStore\migration\Index;
use elfuvo\documentStore\migration\IndexField;

/**
 * Class <?= $className . "\n" ?>
 */
class <?= $className ?> extends Migration
{
    protected const COLLECTION = '<?= $collection ?? ''; ?>';

    /**
     * {@inheritdoc}
     */
    public function up(): bool
    {
        $this->createCollection(self::COLLECTION);

    /*
        $this->createIndex(
        self::COLLECTION,
        new Index([
            'name' => 'active_idx',
            'fields' => [
                new IndexField([
                    'field' => '$.active',
                    'type' => IndexField::TYPE_SMALLINT,
                    'required' => true,
                ])
            ],
            'unique' => false,
        ])
        );
    */
        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function down(): bool
    {
        // return $this->dropIndex(self::COLLECTION, 'active_idx');
        return $this->dropCollection(self::COLLECTION);
    }
}
