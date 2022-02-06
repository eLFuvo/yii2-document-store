<?php
/**
 * Created by PhpStorm
 * User: elfuvo
 * Date: 2021-04-20
 * Time: 15:49
 */

namespace elfuvo\yiiDocumentStore\connection;

use elfuvo\yiiDocumentStore\traits\ObjectEventsTrait;
use Yii;
use yii\base\InvalidConfigException;
use yii\db\Exception;

use function mysql_xdevapi\getSession;

/**
 * Class Connection
 * @package elfuvo\yiiDocumentStore\connection
 */
class Connection extends \elfuvo\documentStore\connection\Connection
{
    use ObjectEventsTrait;

    /**
     * @event Event an event that is triggered after a DB connection is established
     */
    const EVENT_AFTER_OPEN = 'afterOpen';

    /**
     * @event yii\base\Event an event that is triggered right before a top-level transaction is started
     */
    const EVENT_BEGIN_TRANSACTION = 'beginTransaction';

    /**
     * @event yii\base\Event an event that is triggered right after a top-level transaction is committed
     */
    const EVENT_COMMIT_TRANSACTION = 'commitTransaction';

    /**
     * @event yii\base\Event an event that is triggered right after a top-level transaction is rolled back
     */
    const EVENT_ROLLBACK_TRANSACTION = 'rollbackTransaction';

    /**
     * @var bool whether to enable logging of database queries. Defaults to true.
     * You may want to disable this option in a production environment to gain performance
     * if you do not need the information being logged.
     * @since 2.0.12
     * @see enableProfiling
     */
    public bool $enableLogging = true;

    /**
     * @var bool whether to enable profiling of opening database connection and database queries. Defaults to true.
     * You may want to disable this option in a production environment to gain performance
     * if you do not need the information being logged.
     * @since 2.0.12
     * @see enableLogging
     */
    public bool $enableProfiling = true;

    /**
     * @throws \yii\base\InvalidConfigException
     * @throws \yii\db\Exception|\elfuvo\documentStore\Exception
     */
    public function open()
    {
        if ($this->session !== null) {
            return;
        }

        $dsn = $this->getDsn();
        if (!$this->database) {
            throw new InvalidConfigException('Name of the database must be set');
        }

        $token = 'Opening DB connection: ' . preg_replace('#(.+):(.+)@#', '$1:****@', $dsn);
        $enableProfiling = $this->enableProfiling;
        try {
            if ($this->enableLogging) {
                Yii::info($token, __METHOD__);
            }

            if ($enableProfiling) {
                Yii::beginProfile($token, __METHOD__);
            }

            $this->session = getSession($dsn .
                ($this->attributes ? '?' . http_build_query($this->attributes) : '')
            );

            $this->schema = $this->session->getSchema($this->database);
            if (!$this->schema || !$this->schema->existsInDatabase()) {
                throw new Exception('Schema "' . $this->database . '" does not exists.');
            }
            // $this->query('use ' . $this->quoteName($this->database))->execute();

            if ($enableProfiling) {
                Yii::endProfile($token, __METHOD__);
            }
            $this->trigger(self::EVENT_AFTER_OPEN);
        } catch (\mysql_xdevapi\Exception $e) {
            if ($enableProfiling) {
                Yii::endProfile($token, __METHOD__);
            }

            throw new Exception($e->getMessage(), [], (int)$e->getCode(), $e);
        }
    }

    /**
     * @inheritDoc
     */
    public function close()
    {
        if ($this->session !== null) {
            Yii::debug('Closing DB connection: ' . $this->dsn, __METHOD__);
        }
        parent::close();
    }

    /**
     * @inheritDoc
     */
    public function beginTransaction(): void
    {
        $this->trigger(self::EVENT_BEGIN_TRANSACTION);

        parent::beginTransaction();
    }

    /**
     * @inheritDoc
     */
    public function commit(): object
    {
        $result = parent::commit();
        $this->trigger(self::EVENT_COMMIT_TRANSACTION);

        return $result;
    }

    /**
     * @inheritDoc
     */
    public function rollback(): void
    {
        parent::rollback();
        $this->trigger(self::EVENT_ROLLBACK_TRANSACTION);
    }
}
