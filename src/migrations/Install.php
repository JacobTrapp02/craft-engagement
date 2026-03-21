<?php

namespace jtdev\craftengagement\migrations;

use craft\db\Migration;
use craft\db\Table;

/**
 * Install migration for the Engagement plugin.
 */
class Install extends Migration
{
    public function safeUp(): bool
    {
        if (!$this->db->tableExists('{{%ratings_aggregate}}')) {
            $this->createTable('{{%ratings_aggregate}}', [
                'id' => $this->primaryKey(),
                'elementId' => $this->integer()->notNull(),
                'fieldId' => $this->integer()->notNull(),
                'siteId' => $this->integer()->notNull(),
                'average' => $this->decimal(10, 4)->notNull()->defaultValue(0),
                'voteCount' => $this->bigInteger()->unsigned()->notNull()->defaultValue(0),
                'scale' => $this->tinyInteger()->unsigned()->notNull(),
                'dateCreated' => $this->dateTime()->notNull(),
                'dateUpdated' => $this->dateTime()->notNull(),
                'uid' => $this->uid(),
            ]);

            $this->createIndex(
                null,
                '{{%ratings_aggregate}}',
                ['elementId', 'fieldId', 'siteId'],
                true
            );
            $this->addForeignKey(
                null,
                '{{%ratings_aggregate}}',
                ['elementId'],
                Table::ELEMENTS,
                ['id'],
                'CASCADE',
                'CASCADE'
            );
            $this->addForeignKey(
                null,
                '{{%ratings_aggregate}}',
                ['fieldId'],
                Table::FIELDS,
                ['id'],
                'CASCADE',
                'CASCADE'
            );
            $this->addForeignKey(
                null,
                '{{%ratings_aggregate}}',
                ['siteId'],
                Table::SITES,
                ['id'],
                'CASCADE',
                'CASCADE'
            );
        }

        if (!$this->db->tableExists('{{%ratings_votes}}')) {
            $this->createTable('{{%ratings_votes}}', [
                'id' => $this->primaryKey(),
                'topId' => $this->integer()->notNull(),
                'userId' => $this->integer(),
                'sessionId' => $this->string(),
                'rating' => $this->integer()->notNull(),
                'dateCreated' => $this->dateTime()->notNull(),
                'dateUpdated' => $this->dateTime()->notNull(),
                'uid' => $this->uid(),
            ]);

            $this->createIndex(null, '{{%ratings_votes}}', ['topId'], false);
            $this->createIndex(null, '{{%ratings_votes}}', ['userId'], false);
            $this->createIndex(null, '{{%ratings_votes}}', ['sessionId'], false);
            $this->createIndex(
                null,
                '{{%ratings_votes}}',
                ['topId', 'userId'],
                true
            );

            $this->addForeignKey(
                null,
                '{{%ratings_votes}}',
                ['topId'],
                '{{%ratings_aggregate}}',
                ['id'],
                'CASCADE',
                'CASCADE'
            );
            $this->addForeignKey(
                null,
                '{{%ratings_votes}}',
                ['userId'],
                Table::USERS,
                ['id'],
                'SET NULL',
                'CASCADE'
            );
        }

        return true;
    }

    public function safeDown(): bool
    {
        $this->dropTableIfExists('{{%ratings_votes}}');
        $this->dropTableIfExists('{{%ratings_aggregate}}');

        return true;
    }
}
