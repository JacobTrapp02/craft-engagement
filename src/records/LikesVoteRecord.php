<?php

namespace jtdev\craftengagement\records;

use craft\db\ActiveRecord;
use yii\db\ActiveQueryInterface;

/**
 * Active record for engagement_likes_votes.
 */
class LikesVoteRecord extends ActiveRecord
{
    public static function tableName(): string
    {
        return '{{%engagement_likes_votes}}';
    }

    public function getAggregate(): ActiveQueryInterface
    {
        return $this->hasOne(LikesAggregateRecord::class, ['id' => 'aggregateId']);
    }
}
