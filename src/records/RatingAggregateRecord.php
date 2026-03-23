<?php

namespace jtdev\craftengagement\records;

use craft\db\ActiveRecord;
use yii\db\ActiveQueryInterface;

/**
 * Active record for ratings_aggregate.
 */
class RatingAggregateRecord extends ActiveRecord
{
    public static function tableName(): string
    {
        return '{{%ratings_aggregate}}';
    }

    public function getVotes(): ActiveQueryInterface
    {
        return $this->hasMany(VoteRecord::class, ['topId' => 'id']);
    }
}
