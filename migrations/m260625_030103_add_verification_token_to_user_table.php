<?php

use yii\db\Migration;

class m260625_030103_add_verification_token_to_user_table extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        $this->addColumn('user', 'verification_token', $this->string()->defaultValue(null));
        $this->createIndex('idx-user-verification_token', 'user', 'verification_token', true);
    }

    public function safeDown()
    {
        $this->dropIndex('idx-user-verification_token', 'user');
        $this->dropColumn('user', 'verification_token');
    }

    /*
    // Use up()/down() to run migration code without a transaction.
    public function up()
    {

    }

    public function down()
    {
        echo "m260625_030103_add_verification_token_to_user_table cannot be reverted.\n";

        return false;
    }
    */
}
