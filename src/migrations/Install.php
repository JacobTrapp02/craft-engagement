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

        if (!$this->db->tableExists('{{%engagement_likes_aggregate}}')) {
            $this->createTable('{{%engagement_likes_aggregate}}', [
                'id' => $this->primaryKey(),
                'elementId' => $this->integer()->notNull(),
                'fieldId' => $this->integer()->notNull(),
                'siteId' => $this->integer()->notNull(),
                'likeCount' => $this->bigInteger()->unsigned()->notNull()->defaultValue(0),
                'dislikeCount' => $this->bigInteger()->unsigned()->notNull()->defaultValue(0),
                'dateCreated' => $this->dateTime()->notNull(),
                'dateUpdated' => $this->dateTime()->notNull(),
                'uid' => $this->uid(),
            ]);

            $this->createIndex(
                null,
                '{{%engagement_likes_aggregate}}',
                ['elementId', 'fieldId', 'siteId'],
                true
            );
            $this->addForeignKey(
                null,
                '{{%engagement_likes_aggregate}}',
                ['elementId'],
                Table::ELEMENTS,
                ['id'],
                'CASCADE',
                'CASCADE'
            );
            $this->addForeignKey(
                null,
                '{{%engagement_likes_aggregate}}',
                ['fieldId'],
                Table::FIELDS,
                ['id'],
                'CASCADE',
                'CASCADE'
            );
            $this->addForeignKey(
                null,
                '{{%engagement_likes_aggregate}}',
                ['siteId'],
                Table::SITES,
                ['id'],
                'CASCADE',
                'CASCADE'
            );
        }

        if (!$this->db->tableExists('{{%engagement_likes_votes}}')) {
            $this->createTable('{{%engagement_likes_votes}}', [
                'id' => $this->primaryKey(),
                'aggregateId' => $this->integer()->notNull(),
                'userId' => $this->integer(),
                'sessionId' => $this->string(),
                'value' => $this->tinyInteger()->notNull(),
                'dateCreated' => $this->dateTime()->notNull(),
                'dateUpdated' => $this->dateTime()->notNull(),
                'uid' => $this->uid(),
            ]);

            $this->createIndex(null, '{{%engagement_likes_votes}}', ['aggregateId'], false);
            $this->createIndex(null, '{{%engagement_likes_votes}}', ['userId'], false);
            $this->createIndex(null, '{{%engagement_likes_votes}}', ['sessionId'], false);
            $this->createIndex(
                null,
                '{{%engagement_likes_votes}}',
                ['aggregateId', 'userId'],
                true
            );
            $this->createIndex(
                null,
                '{{%engagement_likes_votes}}',
                ['aggregateId', 'sessionId'],
                true
            );

            $this->addForeignKey(
                null,
                '{{%engagement_likes_votes}}',
                ['aggregateId'],
                '{{%engagement_likes_aggregate}}',
                ['id'],
                'CASCADE',
                'CASCADE'
            );
            $this->addForeignKey(
                null,
                '{{%engagement_likes_votes}}',
                ['userId'],
                Table::USERS,
                ['id'],
                'SET NULL',
                'CASCADE'
            );

        }

        if (!$this->db->tableExists('{{%engagement_favorites_aggregate}}')) {
            $this->createTable('{{%engagement_favorites_aggregate}}', [
                'id' => $this->primaryKey(),
                'elementId' => $this->integer()->notNull(),
                'fieldId' => $this->integer()->notNull(),
                'siteId' => $this->integer()->notNull(),
                'favoriteCount' => $this->bigInteger()->unsigned()->notNull()->defaultValue(0),
                'dateCreated' => $this->dateTime()->notNull(),
                'dateUpdated' => $this->dateTime()->notNull(),
                'uid' => $this->uid(),
            ]);

            $this->createIndex(
                null,
                '{{%engagement_favorites_aggregate}}',
                ['elementId', 'fieldId', 'siteId'],
                true
            );
            $this->addForeignKey(
                null,
                '{{%engagement_favorites_aggregate}}',
                ['elementId'],
                Table::ELEMENTS,
                ['id'],
                'CASCADE',
                'CASCADE'
            );
            $this->addForeignKey(
                null,
                '{{%engagement_favorites_aggregate}}',
                ['fieldId'],
                Table::FIELDS,
                ['id'],
                'CASCADE',
                'CASCADE'
            );
            $this->addForeignKey(
                null,
                '{{%engagement_favorites_aggregate}}',
                ['siteId'],
                Table::SITES,
                ['id'],
                'CASCADE',
                'CASCADE'
            );
        }

        if (!$this->db->tableExists('{{%engagement_favorites_entries}}')) {
            $this->createTable('{{%engagement_favorites_entries}}', [
                'id' => $this->primaryKey(),
                'aggregateId' => $this->integer()->notNull(),
                'userId' => $this->integer(),
                'sessionId' => $this->string(),
                'dateCreated' => $this->dateTime()->notNull(),
                'dateUpdated' => $this->dateTime()->notNull(),
                'uid' => $this->uid(),
            ]);

            $this->createIndex(null, '{{%engagement_favorites_entries}}', ['aggregateId'], false);
            $this->createIndex(null, '{{%engagement_favorites_entries}}', ['userId'], false);
            $this->createIndex(null, '{{%engagement_favorites_entries}}', ['sessionId'], false);
            $this->createIndex(
                null,
                '{{%engagement_favorites_entries}}',
                ['aggregateId', 'userId'],
                true
            );
            $this->createIndex(
                null,
                '{{%engagement_favorites_entries}}',
                ['aggregateId', 'sessionId'],
                true
            );

            $this->addForeignKey(
                null,
                '{{%engagement_favorites_entries}}',
                ['aggregateId'],
                '{{%engagement_favorites_aggregate}}',
                ['id'],
                'CASCADE',
                'CASCADE'
            );
            $this->addForeignKey(
                null,
                '{{%engagement_favorites_entries}}',
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
        $this->dropTableIfExists('{{%engagement_favorites_entries}}');
        $this->dropTableIfExists('{{%engagement_favorites_aggregate}}');
        $this->dropTableIfExists('{{%engagement_likes_votes}}');
        $this->dropTableIfExists('{{%engagement_likes_aggregate}}');
        $this->dropTableIfExists('{{%ratings_votes}}');
        $this->dropTableIfExists('{{%ratings_aggregate}}');

        return true;
    }
}
