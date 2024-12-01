<?php

use yii\db\Migration;

/**
 * Handles the creation of table `{{%url_check_results}}`.
 */
class m241201_115301_create_url_check_results_table extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        $this->createTable('url_check_results', [
            'id' => $this->primaryKey(),
            'url_check_id' => $this->integer()->notNull(),
            'timestamp' => $this->dateTime()->notNull(),
            'http_code' => $this->integer()->notNull(),
            'response' => $this->text(),
            'attempt' => $this->integer()->notNull(),
        ]);

        $this->addForeignKey(
            'fk-url_check_results-url_check_id',
            'url_check_results',
            'url_check_id',
            'url_checks',
            'id',
            'CASCADE'
        );
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        $this->dropForeignKey('fk-url_check_results-url_check_id', 'url_check_results');
        $this->dropTable('url_check_results');
    }
}
