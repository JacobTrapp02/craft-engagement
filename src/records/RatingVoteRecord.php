<?php

namespace jtdev\craftengagement\records;

use craft\db\ActiveRecord;
use yii\db\ActiveQueryInterface;

/**
 * Active record for engagement_ratings_votes.
 */
class RatingVoteRecord extends ActiveRecord
{
    public static function tableName(): string
    {
        return '{{%engagement_ratings_votes}}';
    }

    public function getAggregate(): ActiveQueryInterface
    {
        return $this->hasOne(RatingAggregateRecord::class, ['id' => 'aggregateId']);
    }
}
