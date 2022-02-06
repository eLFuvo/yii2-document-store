<?php
/**
 * Created by PhpStorm
 * User: elfuvo
 * Date: 2021-04-25
 * Time: 17:40
 */

namespace elfuvo\yiiDocumentStore\migration;

use elfuvo\documentStore\connection\ConnectionInterface;
use elfuvo\documentStore\migration\MigrationInterface as StoreMigrationInterface;
use mysql_xdevapi\Collection;
use mysql_xdevapi\Schema;
use yii\base\Component;
use yii\db\Connection;
use yii\db\MigrationInterface;
use yii\di\Instance;

/**
 * Class Migration
 * @package elfuvo\yiiDocumentStore\migration
 *
 * @property-read \mysql_xdevapi\Schema $schema
 */
abstract class Migration extends Component implements MigrationInterface, StoreMigrationInterface
{
    use DataMigration;
    use SchemaMigrationTrait;

    /**
     * @var ConnectionInterface
     */
    public ConnectionInterface $store;

    /**
     * @var string|Connection
     */
    public $db = 'db';

    /**
     * @var bool indicates whether the console output should be compacted.
     * If this is set to true, the individual commands ran within the migration will not be output to the console.
     * Default is false, in other words the output is fully verbose by default.
     */
    public bool $compact = false;

    /**
     * @throws \yii\base\InvalidConfigException
     */
    public function init()
    {
        parent::init();
        $this->db = Instance::ensure($this->db, Connection::class);
        $this->store = Instance::ensure(ConnectionInterface::class, ConnectionInterface::class);
    }

    /**
     * @return \elfuvo\documentStore\connection\ConnectionInterface
     */
    protected function getStore(): ConnectionInterface
    {
        return $this->store;
    }

    /**
     * @return \yii\db\Connection
     */
    protected function getDb(): Connection
    {
        return $this->db;
    }

    /**
     * @throws \elfuvo\documentStore\Exception
     */
    public function getSchema(): Schema
    {
        return $this->store->getSchema();
    }

    /**
     * @return bool
     * @throws \elfuvo\documentStore\Exception
     */
    abstract public function up(): bool;

    /**
     * @return bool
     * @throws \elfuvo\documentStore\Exception
     */
    abstract public function down(): bool;

    /**
     * @param string $collection
     * @return \mysql_xdevapi\Collection
     * @throws \elfuvo\documentStore\Exception
     */
    protected function checkCollection(string $collection): Collection
    {
        $table = $this->getSchema()->getCollection($collection);
        if (!$table->existsInDatabase()) {
            $this->getSchema()->createCollection($collection);
        }
        return $table;
    }

    /**
     * Prepares for a command to be executed, and outputs to the console.
     *
     * @param string $description the description for the command, to be output to the console.
     * @return float the time before the command is executed, for the time elapsed to be calculated.
     * @since 2.0.13
     */
    protected function beginCommand(string $description): float
    {
        if (!$this->compact) {
            echo "    > $description ...";
        }
        return microtime(true);
    }

    /**
     * Finalizes after the command has been executed, and outputs to the console the time elapsed.
     *
     * @param float $time the time before the command was executed.
     * @since 2.0.13
     */
    protected function endCommand(float $time)
    {
        if (!$this->compact) {
            echo ' done (time: ' . sprintf('%.3f', microtime(true) - $time) . "s)\n";
        }
    }
}
