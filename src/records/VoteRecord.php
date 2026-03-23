<?php

namespace jtdev\craftengagement\records;

use craft\db\ActiveRecord;
use yii\db\ActiveQueryInterface;

/**
 * Active record for ratings_votes.
 */
class VoteRecord extends ActiveRecord
{
    public static function tableName(): string
    {
        return '{{%ratings_votes}}';
    }

    public function getAggregate(): ActiveQueryInterface
    {
        return $this->hasOne(RatingAggregateRecord::class, ['id' => 'topId']);
    }
}
