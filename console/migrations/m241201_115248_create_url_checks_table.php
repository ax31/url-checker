<?php

use yii\db\Migration;

/**
 * Handles the creation of table `{{%url_checks}}`.
 */
class m241201_115248_create_url_checks_table extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        $this->createTable('url_checks', [
            'id' => $this->primaryKey(),
            'url' => $this->string(255)->notNull(),
            'frequency' => $this->integer()->notNull(),
            'retry_count' => $this->integer()->notNull(),
            'retry_delay' => $this->integer()->notNull(),
        ]);
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        $this->dropTable('url_checks');
    }
}
