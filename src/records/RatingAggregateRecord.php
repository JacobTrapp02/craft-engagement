<?php

namespace jtdev\craftengagement\records;

use craft\db\ActiveRecord;
use yii\db\ActiveQueryInterface;

/**
 * Active record for engagement_ratings_aggregate.
 */
class RatingAggregateRecord extends ActiveRecord
{
    public static function tableName(): string
    {
        return '{{%engagement_ratings_aggregate}}';
    }

    public function getVotes(): ActiveQueryInterface
    {
        return $this->hasMany(RatingVoteRecord::class, ['aggregateId' => 'id']);
    }
}
