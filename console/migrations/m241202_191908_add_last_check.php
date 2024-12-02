<?php

use yii\db\Migration;

class m241202_191908_add_last_check extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        $this->addColumn('url_checks', 'last_check', $this->timestamp()->null()->defaultValue(null));
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        $this->dropColumn('url_checks', 'last_check');
    }

    /*
    // Use up()/down() to run migration code without a transaction.
    public function up()
    {

    }

    public function down()
    {
        echo "m241202_191908_add_last_check cannot be reverted.\n";

        return false;
    }
    */
}
