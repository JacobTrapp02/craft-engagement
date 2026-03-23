<?php

namespace jtdev\craftengagement\records;

use craft\db\ActiveRecord;
use yii\db\ActiveQueryInterface;

/**
 * Active record for engagement_likes_aggregate.
 */
class LikesAggregateRecord extends ActiveRecord
{
    public static function tableName(): string
    {
        return '{{%engagement_likes_aggregate}}';
    }

    public function getVotes(): ActiveQueryInterface
    {
        return $this->hasMany(LikesVoteRecord::class, ['aggregateId' => 'id']);
    }
}
